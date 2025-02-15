mod flatten_structure;
use std::{ fmt::Debug, fs };
use std::collections::HashMap;
use std::io::BufReader;
use std::path::PathBuf;
use rayon::prelude::*;
use rusqlite::{ Connection, Result };
use xmltree::{ Element, XMLNode };
use serde_json::Value;
use flatten_structure::flatten_structure;
use html2md::parse_html;

#[derive(Debug)]
struct TitleRecord {
    number: i32,
    structure_reference: String,
}

fn db_connection() -> Result<Connection> {
    // Connect to SQLite database (or create it if it doesn't exist)
    Connection::open("database/database.sqlite")
}

/*
 * One-time DFS to build a map of all SECTION elements keyed by their "N" attribute.
 */
fn build_section_map<'a>(elem: &'a Element, map: &mut HashMap<String, &'a Element>) {
    if let Some(type_attr) = elem.attributes.get("TYPE") {
        // If it's a SECTION, save under the "N" attribute
        if type_attr == "SECTION" {
            if let Some(n_attr) = elem.attributes.get("N") {
                map.insert(n_attr.clone(), elem);
            }
        }
    }
    // Recurse into children
    for child in &elem.children {
        if let XMLNode::Element(child_elem) = child {
            build_section_map(child_elem, map);
        }
    }
}

fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    String::from_utf8(section_string).unwrap()
}

fn section_corrections(section_string: &str) -> String {
    section_string.replace("<HEAD>", "<h5>").replace("</HEAD>", "</h5>")
}

/*
 * Get the path of the section from the array of parents in the structure JSON
 */
fn get_section_path(parents: &Vec<Value>) -> String {
    parents
        .iter()
        .map(|parent| {
            let parent_obj = parent.as_object().unwrap();
            let parent_label = parent_obj.get("label_level").unwrap().as_str().unwrap().trim();
            parent_label.to_lowercase().replace(' ', "-")
        })
        .collect::<Vec<String>>()
        .join("/")
}

fn main() -> Result<()> {
    let db = db_connection()?;
    let mut stmt = db.prepare(
        "SELECT number, structure_reference FROM titles WHERE reserved = false"
    )?;

    // Query data and collect results into a Vec
    let title_results: Vec<Result<TitleRecord>> = stmt
        .query_map([], |row| {
            Ok(TitleRecord {
                number: row.get(0)?,
                structure_reference: row.get(1)?,
            })
        })?
        .collect();

    let title_file_directory = "./storage/app/private/ecfr/current";
    let markdown_directory = "./storage/app/private/ecfr/markdown";

    // Parallel loop. FAST (may be too much for production)
    title_results.into_par_iter().for_each(|title_result| {
        if let Ok(title) = title_result {
            let title_filename = format!("{}/title-{}.xml", title_file_directory, title.number);
            println!("Title Filename: {}", title_filename);

            // Parse the entire XML file once
            let file = fs::File::open(&title_filename).unwrap();
            let root = Element::parse(BufReader::new(file)).unwrap();

            // Build a HashMap for quick section lookups
            let mut section_map = HashMap::new();
            build_section_map(&root, &mut section_map);

            // Read structure JSON file
            let structure_file = fs::File::open(&title.structure_reference).unwrap();
            let structure: Value = serde_json::from_reader(structure_file).unwrap();
            let flat_structure = flatten_structure(structure);

            // Convert sections in parallel for speed if you'd like
            flat_structure.into_par_iter().for_each(|section| {
                let section_obj = section.as_object().unwrap();
                if section_obj.get("identifier").is_none() || section_obj.get("parents").is_none() {
                    return;
                }

                let identifier = section_obj.get("identifier").unwrap().as_str().unwrap().trim();
                let parents = section_obj.get("parents").unwrap().as_array().unwrap();

                if let Some(section_element) = section_map.get(identifier) {
                    let section_string = xml_element_to_string(section_element);

                    let corrected_string = section_corrections(&section_string);
                    let section_markdown = parse_html(&corrected_string);

                    // Write file
                    let section_path = get_section_path(parents);
                    let section_filename = format!(
                        "{}/{}/{}.md",
                        markdown_directory,
                        section_path,
                        identifier
                    );

                    let section_pathbuf = PathBuf::from(&section_filename);
                    match fs::create_dir_all(section_pathbuf.parent().unwrap()) {
                        Ok(_) => (),
                        Err(e) => {
                            println!(
                                "Error creating directory {}: {}",
                                section_pathbuf.parent().unwrap().display(),
                                e
                            );
                            return;
                        }
                    }
                    match fs::write(&section_filename, section_markdown) {
                        Ok(_) => (),
                        Err(e) => println!("Error writing {}: {}", section_filename, e),
                    }
                }
            });
        }
    });

    Ok(())
}
