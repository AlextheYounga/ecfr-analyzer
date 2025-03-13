use std::fs;
use std::collections::HashMap;
use std::io::BufReader;
use std::path::Path;
use indicatif::{ProgressBar, ProgressStyle};
use rayon::prelude::*;
use xmltree::{Element, XMLNode};
use serde_json::Value;
use html2md::parse_html;

pub fn run(input_folder: &str, output_folder: &str, structure_folder: &str) {
	let title_numbers = 1..51;
	let pb = instantiate_progress_bar();

    title_numbers.into_par_iter().for_each(|title_number| {
		let title_filename = format!("{}/title-{}.xml", input_folder, title_number);

		if ! Path::new(&title_filename).exists() {
			return;
		}	

		let file = fs::File::open(&title_filename).unwrap();
		let root = Element::parse(BufReader::new(file)).unwrap();

		let mut section_map = HashMap::new();
		build_section_map(&root, &mut section_map);

		let structure_reference = format!("{}/title-{}-structure.json", structure_folder, title_number);
		let structure_file = fs::File::open(structure_reference).unwrap();
		let structure: Value = serde_json::from_reader(structure_file).unwrap();
		let flat_structure = flatten_structure(structure);

		let title_markdown_dir = format!("{}/title-{}", output_folder, title_number);
		fs::create_dir_all(&title_markdown_dir)
			.unwrap_or_else(|_| panic!("Failed to create directory: {}", title_markdown_dir));

		flat_structure.into_par_iter().for_each(|section| {
			let section_obj = section.as_object().unwrap();
			let identifier = match section_obj.get("identifier").and_then(|v| v.as_str()) {
				Some(id) => id.trim(),
				None => return,
			};

			if let Some(section_element) = section_map.get(identifier) {
				let section_string = xml_element_to_string(section_element);
				let corrected_string = section_string.replace("<HEAD>", "<h5>").replace("</HEAD>", "</h5>");
				let section_markdown = parse_html(&corrected_string);

				let section_filename = format!("{}/{}.md", title_markdown_dir, identifier);
				if let Err(e) = fs::write(&section_filename, section_markdown) {
					println!("Error writing {}: {}", section_filename, e);
				}
			}
		});
		pb.inc(1); // increment progress bar
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

fn build_section_map<'a>(elem: &'a Element, map: &mut HashMap<String, &'a Element>) {
    if let Some(type_attr) = elem.attributes.get("TYPE") {
        if type_attr == "SECTION" {
            if let Some(n_attr) = elem.attributes.get("N") {
                map.insert(n_attr.clone(), elem);
            }
        }
    }
    for child in &elem.children {
        if let XMLNode::Element(child_elem) = child {
            build_section_map(child_elem, map);
        }
    }
}

pub fn flatten_structure(structure: Value) -> Vec<Value> {
    let mut sections = Vec::new();
    walk_structure_sections(&structure, &mut sections);
    sections
}

fn walk_structure_sections(structure: &Value, sections: &mut Vec<Value>) {
    if let Some(children) = structure.get("children") {
        if let Some(children_array) = children.as_array() {
            for child in children_array {
                walk_structure_sections(child, sections);
            }
        }
    } else if let Some(obj) = structure.as_object() {
        sections.push(Value::Object(obj.clone()));
    }
}

fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    String::from_utf8(section_string).unwrap()
}

