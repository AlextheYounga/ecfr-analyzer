use std::{ fmt::Debug, fs };
use std::collections::HashMap;
use std::io::BufReader;
use std::path::PathBuf;
use rayon::prelude::*;
use rusqlite::{ Connection, Result };
use xmltree::{ Element, XMLNode };
use serde_json::Value;

use html2md::parse_html;

#[derive(Debug)]
struct TitleRecord {
    number: i32,
    structure_reference: String,
}

// Talk to our Laravel DB
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

// We will create a list of sections here each containing a parents array.
pub fn flatten_structure(structure: Value) -> Vec<Value> {
    // This will hold the flattened structures
    let mut sections = Vec::new();

    // Recursively flatten the JSON structure
    walk_structure_sections(&structure, &Vec::new(), &mut sections);

    return sections;
}

/* 
*  	Recursively walk `structure`. If there's a "children" field:
*  		- Remove it from the current node (so we don't duplicate it later).
*  		- Recursively flatten each child, accumulating parents along the way.
* 	Otherwise, if there's no "children" field:
*  		- It's a leaf node, so we insert the accumulated `parents` and push it into the `sections` vector.	
*/ 
fn walk_structure_sections(structure: &Value, parents: &Vec<Value>, sections: &mut Vec<Value>) {
    if let Some(children) = structure.get("children") {
        // If `children` is present and is an array
        if let Some(children_array) = children.as_array() {
            // Clone the current object so we can strip out the "children"
            if let Some(obj) = structure.as_object() {
                let mut parent_obj = obj.clone();
                parent_obj.remove("children");

                // Convert our "parent_obj" back to a Value
                let parent_value = Value::Object(parent_obj);

                // Append that parent to the list of parents
                let mut new_parents = parents.clone();
                new_parents.push(parent_value);

                // Recurse for each child
                for child in children_array {
                    walk_structure_sections(child, &new_parents, sections);
                }
            }
        }
    } else {
        // No "children" => This is a leaf node
        if let Some(obj) = structure.as_object() {
            let mut leaf_obj = obj.clone();
            // Insert the "parents" array
            leaf_obj.insert("parents".to_string(), Value::Array(parents.clone()));
            sections.push(Value::Object(leaf_obj));
        }
    }
}

fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    String::from_utf8(section_string).unwrap()
}

/*
 * Get the path of the section from the array of parents in the structure JSON
 */
 fn get_section_path(parents: &Vec<Value>) -> String {
    parents
        .iter()
        .map(|parent| {
            let parent_obj = parent.as_object().unwrap();
			// Manually build parent label because of bad data entry in gov db.
			let parent_type = parent_obj.get("type").unwrap().as_str().unwrap().trim();
			let parent_id = parent_obj.get("identifier").unwrap().as_str().unwrap().trim();
			let parent_label = parent_type.to_lowercase().replace(' ', "-") + "-" + &parent_id.to_lowercase().replace(' ', "-");
            parent_label
        })
        .collect::<Vec<String>>()
        .join("/")
}

pub fn run() -> Result<()> {
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

		let title_file_directory = "./storage/app/private/ecfr/xml";
		let markdown_directory = "./storage/app/private/ecfr/markdown/nested";

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
                    let corrected_string = section_string.replace("<HEAD>", "<h5>").replace("</HEAD>", "</h5>");
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

