use std::fs;
use std::collections::HashMap;
use std::io::BufReader;
use std::path::{Path, PathBuf};
use indicatif::{ProgressBar, ProgressStyle};
use rayon::prelude::*;
use xmltree::{ Element, XMLNode };
use serde_json::Value;
use html2md::parse_html;



pub fn run(input_folder: &str, output_folder: &str, structure_folder: &str) {
	let title_numbers = 1..51;
	let pb = instantiate_progress_bar();

    // Parallel loop. FAST (may be too much for production)
    title_numbers.into_par_iter().for_each(|title_number| {
		let title_filename = format!("{}/title-{}.xml", input_folder, title_number);
		if ! Path::new(&title_filename).exists() {
			pb.inc(1); // increment progress
			return;
		}	

		// Parse the entire XML file once
		let file = fs::File::open(&title_filename).unwrap();
		let root = Element::parse(BufReader::new(file)).unwrap();

		// Build a HashMap for quick section lookups
		let mut section_map = HashMap::new();
		build_section_map(&root, &mut section_map);

		// Read structure JSON file
		let structure_reference = format!("{}/title-{}-structure.json", structure_folder, title_number);
		let structure_file = fs::File::open(&structure_reference).unwrap();
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
				let section_filename = format!("{}/{}/section-{}.md", output_folder, section_path, identifier);

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
		pb.inc(1); // increment progress
    });
	pb.finish_with_message("Done!");
}

fn instantiate_progress_bar() -> ProgressBar {
	// Create a new progress bar with an upper bound (e.g. 100 items)
	let pb = ProgressBar::new(50);

	// You can set a custom style similar to Cargo's
	pb.set_style(
		ProgressStyle::default_bar()
			.template("{spinner:.green} [{elapsed_precise}] [{wide_bar:.cyan/blue}] {pos:>7}/{len:7}")
			.expect("Template was invalid")
			.progress_chars("#>-"),
	);

	return pb;
}

fn parameterize(text: &str) -> String {
	let mut formatted = text.chars()
		.filter(|c| c.is_alphanumeric() || c.is_whitespace())
		.collect::<String>()
		.replace(' ', "-");

	// Remove prepositions and other stop words
	let stop_words = vec!["the", "at", "to", "a", "of", "in", "and", "on", "for", "by", "with", "With", 
	"or", "from", "Applicable", "Concerning", "Regarding", "Respecting", "Respect"];
	for word in stop_words {
		let replace_word = format!("-{}-", word);
		formatted = formatted.replace(&replace_word, "-");
	}

	// Remove multiple dashes
	while formatted.contains("--") {
		formatted = formatted.replace("--", "-");
	}

	return formatted;
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
			let parent_id = parent_obj.get("identifier").unwrap().as_str().unwrap().trim();
			let parent_type = parent_obj.get("type").unwrap().as_str().unwrap().trim();
			let parent_label = parent_obj.get("label_description").unwrap().as_str().unwrap_or("").trim();
			let formatted_label = format!("{}-{}-{}", parameterize(parent_type), parent_id, parameterize(parent_label));

			// If filename too long
			if formatted_label.len() > 255 {
				// Get the first 6 words of the label
				let short_description = parameterize(parent_label)
					.split("-")
					.take(6)
					.collect::<Vec<&str>>()
					.join("-");	
				let short_label = format!("{}-{}-{}", parameterize(parent_type), parent_id, short_description);
				return short_label;
			}

            formatted_label
        })
        .collect::<Vec<String>>()
        .join("/")
}



