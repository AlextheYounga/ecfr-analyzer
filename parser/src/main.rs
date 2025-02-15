mod flatten_structure;
use std::{ fmt::Debug, fs };
use std::path::PathBuf;
use std::io::BufReader;
use rayon::prelude::*;
use rusqlite::{ Connection, Result };
use xmltree::{ Element, XMLNode };
use serde_json::Value;
use flatten_structure::flatten_structure;
use scraper::Html;
use html2md::parse_html;

#[derive(Debug)]
struct TitleRecord {
    id: i32,
    number: i32,
    name: String,
    latest_amended_on: String,
    latest_issue_date: String,
    up_to_date_as_of: String,
    reserved: bool,
    structure: String,
    created_at: String,
    updated_at: String,
}

fn db_connection() -> Result<Connection> {
    // Connect to SQLite database (or create it if it doesn't exist)
    let conn = Connection::open("database/database.sqlite")?;
    Ok(conn)
}

fn find_section_element<'a>(elem: &'a Element, n_val: &str) -> Option<&'a Element> {
    // Access the attributes map
    let attr_n = elem.attributes.get("N");
    let attr_type = elem.attributes.get("TYPE");

    // Compare them with what we want
    if attr_n == Some(&n_val.to_string()) && attr_type == Some(&"SECTION".to_string()) {
        return Some(elem);
    } else {
        // Traverse child nodes recursively
        for child in &elem.children {
            if let XMLNode::Element(child_elem) = child {
                find_section_element(child_elem, n_val);
            }
        }
    }
	return None;
}

fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    let section_string = String::from_utf8(section_string).unwrap();
    return section_string;
}

fn section_corrections(section_string: &str) -> String {
    return section_string.replace("<HEAD>", "<h5>").replace("</HEAD>", "</h5>");
}

/*
 * Get the path of the section from the array of parents in the structure JSON
 */
fn get_section_path(parents: &Vec<Value>) -> String {
    let parent_path = parents
        .iter()
        .map(|parent| {
            let parent_obj = parent.as_object().unwrap();
            let parent_label = parent_obj.get("label_level").unwrap().as_str().unwrap().trim();
            return parent_label.to_lowercase().replace(" ", "-");
        })
        .collect::<Vec<String>>()
        .join("/");
    return parent_path;
}

fn main() -> Result<()> {
    let db = db_connection().unwrap();
    let mut stmt = db.prepare("SELECT * FROM titles WHERE reserved = false")?;

    // Query data and collect results into a Vec
    let title_results: Vec<Result<TitleRecord>> = stmt
        .query_map([], |row| {
            Ok(TitleRecord {
                id: row.get(0)?,
                number: row.get(1)?,
                name: row.get(2)?,
                latest_amended_on: row.get(3)?,
                latest_issue_date: row.get(4)?,
                up_to_date_as_of: row.get(5)?,
                reserved: row.get(6)?,
                structure: row.get(7)?,
                created_at: row.get(8)?,
                updated_at: row.get(9)?,
            })
        })?
        .collect();

    let title_file_directory = "./storage/app/private/ecfr/current";
    let markdown_directory = "./storage/app/private/ecfr/markdown";

    // Convert the Vec into a parallel iterator
    // for title_result in title_results {
    // let title = title_result.unwrap();
    // Convert the Vec into a parallel iterator
    for title_result in title_results {
        if let Ok(title) = title_result {
            // Read XML file to string
            let title_filename = format!("{}/title-{}.xml", title_file_directory, title.number);
            println!("Title Filename: {}", title_filename);
            let file_path = PathBuf::from(&title_filename);
            let file = fs::File::open(&file_path).unwrap();
            let file = BufReader::new(file);
            let root = Element::parse(file).unwrap();

            let structure: Value = serde_json::from_str(&title.structure).unwrap();
            let flat_structure = flatten_structure(structure);

            flat_structure.into_par_iter().for_each(|section| {
                // for section in flat_structure {
                let section_obj = section.as_object().unwrap();
                if section_obj.get("identifier").is_none() || section_obj.get("parents").is_none() {
                    return;
                }

                let identifier = section_obj.get("identifier").unwrap().as_str().unwrap().trim();
                let parents = section_obj.get("parents").unwrap().as_array().unwrap();

                // Get XML section elements
                let section_element = find_section_element(&root, identifier);
                if section_element.is_some() {
                    let section_element = section_element.unwrap();
                    let section_string = xml_element_to_string(&section_element);

                    // Convert to HTML then to Markdown
                    let corrected_string = section_corrections(&section_string);
                    let section_html = Html::parse_fragment(&corrected_string);
                    let html_string = section_html.html().to_string();
                    let section_markdown = parse_html(&html_string);

                    // Write Markdown file
                    let section_path = get_section_path(parents);
                    let section_filename = format!(
                        "{}/{}/{}.md",
                        markdown_directory,
                        section_path,
                        identifier
                    );
                    let section_pathbuf = PathBuf::from(&section_filename);
                    fs::create_dir_all(section_pathbuf.parent().unwrap()).unwrap();
                    fs::write(&section_filename, section_markdown).unwrap();
                }
            });
        }
    }

    Ok(())
}
