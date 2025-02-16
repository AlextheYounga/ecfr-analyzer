use std::collections::HashMap;
use std::fs;
use std::io::BufReader;
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

fn db_connection() -> Result<Connection> {
    Connection::open("database/database.sqlite")
}

/// Recursively builds a map of XML elements keyed by their "N" attribute,
/// but only for elements whose TYPE attribute is "SECTION".
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

/// Converts an XML element to a string.
fn xml_element_to_string(element: &Element) -> String {
    let mut section_string = Vec::new();
    element.write(&mut section_string).unwrap();
    String::from_utf8(section_string).unwrap()
}

/// Given a document type, returns the appropriate Markdown header prefix.
///
/// The mapping is as follows:
/// - Title:       `#`
/// - Subtitle:    `##`
/// - Chapter:     `###`
/// - Subchapter:  `####`
/// - Part:        `###`
/// - Subpart:     `####`
/// - Section:     `#####`
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

/// Recursively traverses the JSON structure, appending Markdown text to `output`.
///
/// For each node (assumed to be an object) the function:
/// 1. Determines the document type (defaulting to `"section"`) and uses that
///    to generate a Markdown header (using the mapping given above).
/// 2. Chooses a title from the `"caption"` field (or, if missing, the `"identifier"`).
/// 3. Looks up the corresponding XML element (via the identifier) in `section_map`
///    and converts its content to Markdown.
/// 4. Processes any children found in the `"children"` array.
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

pub fn run() -> Result<()> {
    let db = db_connection()?;
    let mut stmt = db.prepare(
        "SELECT number, structure_reference FROM titles WHERE reserved = false"
    )?;
    let title_results: Vec<Result<TitleRecord>> = stmt
        .query_map([], |row| {
            Ok(TitleRecord {
                number: row.get(0)?,
                structure_reference: row.get(1)?,
            })
        })?
        .collect();

    let title_file_directory = "./storage/app/private/ecfr/current/documents/xml";
    let markdown_directory = "./storage/app/private/ecfr/current/documents/markdown/full";
    fs::create_dir_all(markdown_directory).unwrap_or_else(|_|
        panic!("Failed to create directory: {}", markdown_directory)
    );

    // Process each title in parallel.
    title_results.into_par_iter().for_each(|title_result| {
        if let Ok(title) = title_result {
            let title_filename = format!("{}/title-{}.xml", title_file_directory, title.number);
            println!("Processing Title {}: {}", title.number, title_filename);
            let file = fs::File::open(&title_filename).unwrap();
            let root = Element::parse(BufReader::new(file)).unwrap();

            // Build a map from section identifiers to XML elements.
            let mut section_map = HashMap::new();
            build_section_map(&root, &mut section_map);

            // Read and parse the JSON structure file.
            let structure_file = fs::File::open(&title.structure_reference).unwrap();
            let structure: Value = serde_json::from_reader(structure_file).unwrap();

            // Build the complete Markdown output by traversing the JSON structure.
            let mut markdown_output = String::new();
            process_structure(&structure, &section_map, &mut markdown_output);

            // Write the combined Markdown file (one file per title).
            let markdown_filename = format!("{}/title-{}.md", markdown_directory, title.number);
            if let Err(e) = fs::write(&markdown_filename, markdown_output) {
                println!("Error writing {}: {}", markdown_filename, e);
            }
        }
    });
    Ok(())
}
