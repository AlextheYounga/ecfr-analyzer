use std::{collections::HashMap, path::Path};
use std::fs;
use std::io::BufReader;
use std::env;
use dotenv::dotenv;
// use rayon::prelude::*;
use rusqlite::{ Connection, Result };
use xmltree::{ Element, XMLNode };

#[derive(Debug)]
struct VersionRecord {
    issue_date: String,
    part: String,
}

fn db_connection() -> Result<Connection> {
    Connection::open("database/database.sqlite")
}

fn get_large_documents(db: &Connection) -> Vec<i32> {
	let sql_str = "SELECT title_id FROM title_entities WHERE size > 100000000 AND type = 'title';";
	let mut stmt = db.prepare(sql_str).unwrap();
	let results: Vec<i32> = stmt.query_map([], |row| row.get(0))
		.unwrap()
		.map(|x| x.unwrap())
		.collect();
	
	return results;
}

fn read_latest_issue_document(storage_folder: &str, doc_num: i32) -> Element {
	let current_folder = format!("{}/ecfr/current/documents/xml", storage_folder);
	let filepath = format!("{}/title-{}.xml", current_folder, doc_num);

	let file = fs::File::open(&filepath).expect(&format!("Failed to open file: {}", filepath));
	let reader = BufReader::new(file);
	let root = Element::parse(reader).expect(&format!("Failed to parse XML file: {}", filepath));
	return root;
}	

fn read_document_part_file(xml_file: &str) -> Element {
	let file = fs::File::open(xml_file).expect(&format!("Failed to open file: {}", xml_file));
	let reader = BufReader::new(file);
	let root = Element::parse(reader).expect(&format!("Failed to parse XML file: {}", xml_file));
	return root;
}

fn main() -> Result<()> {
	dotenv().ok();
	
    let db = db_connection()?;
	let storage_folder = env::var("STORAGE_DRIVE").unwrap();
	let xml_directory = format!("{}/ecfr/historical/xml", storage_folder);
	let large_documents = get_large_documents(&db);

	for doc_num in large_documents {
		let base_document = read_latest_issue_document(&storage_folder, doc_num);
		let title_partials_folder = format!("{}/title-{}/partials", xml_directory, doc_num);
		let sql_str = format!("SELECT DISTINCT issue_date, part FROM versions WHERE title_id = {}", doc_num);
		let mut stmt = db.prepare(&sql_str)?;
	
		let version_results: Vec<Result<VersionRecord>> = stmt
			.query_map([], |row| {
				Ok(VersionRecord {
					issue_date: row.get(0)?,
					part: row.get(1)?,
				})
			})?
			.collect();

		let mut version_map = HashMap::new();
		for version in version_results {
			let version = version.unwrap();
			version_map.entry(version.issue_date.clone())
				.or_insert_with(Vec::new)
				.push(version.part.clone());
		}

		// rayon::ThreadPoolBuilder::new().num_threads(20).build_global().unwrap(); // Set number of threads to 20
		// version_map.par_iter().for_each(|(issue_date, parts)| {
		for (issue_date, parts) in version_map.iter() {
			println!("Processing title-{} for issue date: {}", doc_num, issue_date);
			let mut document_clone = base_document.clone();
			for part in parts {
				let version_folder = format!("{}/{}", title_partials_folder, issue_date);
				let xml_file = format!("{}/_title-{}-{}-part-{}.xml", version_folder, doc_num, issue_date, part);

				if Path::new(&xml_file).exists() == false {
					// eprintln!("XML does not exist: {}", xml_file);
					continue;
				}

				let document_part = read_document_part_file(&xml_file);

				for child in document_part.children.iter() {
					if let XMLNode::Element(e) = child {
						if e.name.contains("DIV") && e.attributes.get("N") == Some(part) && e.attributes.get("TYPE") == Some(&"PART".to_string()) {
							match document_clone.get_mut_child("DIV") {
								Some(div) => {
									div.children.push(XMLNode::Element(e.clone()));
								}
								None => {
									eprintln!("Failed to find matching DIV in document_clone for part: {}", part);
								}
							}
						}
					}
				}
			}

			let title_folder = format!("{}/title-{}", xml_directory, doc_num);
			let output_file = format!("{}/title-{}-{}.xml", title_folder, doc_num, issue_date);
			let mut output = fs::File::create(&output_file).expect(&format!("Failed to create output file: {}", output_file));
			document_clone.write(&mut output).expect(&format!("Failed to write to output file: {}", output_file));
			break;
		// });
		}
	}

	Ok(())
}
