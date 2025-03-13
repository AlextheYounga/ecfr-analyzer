use std::collections::HashMap;
use std::fs;
use std::io::BufReader;
use std::path::Path;
use indicatif::{ProgressBar, ProgressStyle};
use rayon::prelude::*;
use xmltree::{ Element, XMLNode };
use serde_json::Value;
use html2md::parse_html;

pub fn run(input_folder: &str, output_folder: &str, structure_folder: &str) {
	let title_numbers = 1..51;
	let pb = instantiate_progress_bar();

    // Process each title in parallel.
    title_numbers.into_par_iter().for_each(|title_number| {
		let title_filename = format!("{}/title-{}.xml", input_folder, title_number);

		if ! Path::new(&title_filename).exists() {
			return;
		}	

		println!("Processing Title {}: {}", title_number, title_filename);
		let file = fs::File::open(&title_filename).unwrap();
		let root = Element::parse(BufReader::new(file)).unwrap();

		// Build a map from section identifiers to XML elements.
		let mut section_map = HashMap::new();
		build_section_map(&root, &mut section_map);

		// Read and parse the JSON structure file.
		let structure_reference = format!("{}/title-{}-structure.json", structure_folder, title_number);
		let structure_file = fs::File::open(&structure_reference).unwrap();
		let structure: Value = serde_json::from_reader(structure_file).unwrap();

		// Build the complete Markdown output by traversing the JSON structure.
		let mut markdown_output = String::new();
		process_structure(&structure, &section_map, &mut markdown_output);

		// Write the combined Markdown file (one file per title).
		let markdown_filename = format!("{}/title-{}.md", output_folder, title_number);
		if let Err(e) = fs::write(&markdown_filename, markdown_output) {
			println!("Error writing {}: {}", markdown_filename, e);
		}
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

/*
* Recursively builds a map of XML elements keyed by their "N" attribute,
* but only for elements whose TYPE attribute is "SECTION".
*/
fn build_section_map(elem: &Element, map: &mut HashMap<String, Element>) {
    if let Some(type_attr) = elem.attributes.get("TYPE") {
        if type_attr == "SECTION" {
            if let Some(n_attr) = elem.attributes.get("N") {
                map.insert(n_attr.clone(), elem.clone());
            }
        }
    }
    for child in &elem.children {
        if let XMLNode::Element(child_elem) = child {
            build_section_map(child_elem, map);
        }
    }
}

// Converts an XML element to a string.
fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    String::from_utf8(section_string).unwrap()
}

/*
* Given a document type, returns the appropriate Markdown header prefix.
* The mapping is as follows:
	- Title:       `#`
	- Subtitle:    `##`
	- Chapter:     `###`
	- Subchapter:  `####`
	- Part:        `###`
	- Subpart:     `####`
	- Section:     `#####`
*
*/
fn header_prefix(doc_type: &str) -> String {
    (
        match doc_type.to_lowercase().as_str() {
            "title" => "#",
            "subtitle" => "##",
            "chapter" => "###",
            "subchapter" => "####",
            "part" => "###",
            "subpart" => "####",
            "section" => "#####",
            _ => "#####", // default level if unknown
        }
    ).to_string()
}

/* Recursively traverses the JSON structure, appending Markdown text to `output`.
*
* For each node (assumed to be an object) the function:
* 1. Determines the document type (defaulting to `"section"`) and uses that
*    to generate a Markdown header (using the mapping given above).
* 2. Chooses a title from the `"caption"` field (or, if missing, the `"identifier"`).
* 3. Looks up the corresponding XML element (via the identifier) in `section_map`
*    and converts its content to Markdown.
* 4. Processes any children found in the `"children"` array.
*/
fn process_structure(node: &Value, section_map: &HashMap<String, Element>, output: &mut String) {
    if let Some(obj) = node.as_object() {
        // Determine the document type (default to "section" if not provided)
        let doc_type = obj
            .get("type")
            .and_then(|v| v.as_str())
            .unwrap_or("section");
        let header = header_prefix(doc_type);

        // Use "caption" if available; otherwise, use "identifier"
        let title_text = obj
            .get("caption")
            .and_then(|v| v.as_str())
            .or_else(|| obj.get("identifier").and_then(|v| v.as_str()))
            .unwrap_or("");

        if !title_text.is_empty() {
            output.push_str(&format!("{} {}\n\n", header, title_text));
        }

        // If an identifier exists, try to look up and convert the XML section.
        if let Some(identifier) = obj.get("identifier").and_then(|v| v.as_str()) {
            if let Some(xml_elem) = section_map.get(identifier) {
                let section_string = xml_element_to_string(xml_elem);
                // Replace XML <HEAD> tags with HTML header tags before converting.
                let corrected_string = section_string
                    .replace("<HEAD>", "<h5>")
                    .replace("</HEAD>", "</h5>");
                let section_markdown = parse_html(&corrected_string);
                output.push_str(&section_markdown);
                output.push_str("\n\n");
            }
        }

        // Recurse into any children.
        if let Some(children) = obj.get("children") {
            if let Some(child_array) = children.as_array() {
                for child in child_array {
                    process_structure(child, section_map, output);
                }
            }
        }
    } else if let Some(array) = node.as_array() {
        for child in array {
            process_structure(child, section_map, output);
        }
    }
}


