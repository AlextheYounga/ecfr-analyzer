use std::collections::HashMap;
use std::fs;
use std::io::BufReader;
use std::env;
use std::path::PathBuf;
use dotenv::dotenv;
use rayon::prelude::*;
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

fn get_latest_issue_date(doc_num: i32, db: &Connection) -> String {
	let sql_str = format!("SELECT latest_issue_date FROM titles WHERE id = {} LIMIT 1", doc_num);
	let mut stmt = db.prepare(&sql_str).unwrap();
	let mut latest_issue_date = String::new();	

	let results: Vec<Result<String>> = stmt.query_map([], |row| {
		Ok(row.get(0)?)
	}).unwrap().collect();
	
	// There should only be one result
	for result in results {
		latest_issue_date = result.unwrap();
	}
	return latest_issue_date;
}

fn read_latest_issue_document(xml_dir: &str, doc_num: i32, issue_date: &str) -> BufReader<fs::File> {
	let title_folder = format!("{}/title-{}", xml_dir, doc_num);
	let filename = format!("/title-{}-{}.xml", doc_num, issue_date);
	let filepath = format!("{}/{}", title_folder, filename);

	let file = fs::File::open(&filepath).expect(&format!("Failed to open file: {}", filepath));
	let reader = BufReader::new(file);
	return reader;
}

fn main() -> Result<()> {
	dotenv().ok();
	let storage_folder = env::var("STORAGE_FOLDER").unwrap_err();
	let large_documents = [40];
    let db = db_connection()?;
	let xml_directory = format!("{}/ecfr/xml", storage_folder);
	for doc_num in large_documents {
		let latest_issue_date = get_latest_issue_date(doc_num, &db);
		let base_document = read_latest_issue_document(&xml_directory, doc_num, &latest_issue_date);
		

		let title_folder = format!("{}/title-{}/partials", xml_directory, doc_num);
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

		for version in version_results {
			let version = version?;
			let issue_date = version.issue_date;
			let part = version.part;
			let xml_file = format!("{}/{}/_title-{}-{}-part-{}.xml.zip", title_folder, issue_date, doc_num, issue_date, part);
			println!("Processing: {}", xml_file);

			match fs::File::open(&xml_file) {
				Ok(file) => {
					let reader = BufReader::new(file);
					match Element::parse(reader) {
						Ok(mut root) => {
							if let Some(node) = root.get_mut_child("part") {
								if let Some(node) = node.get_mut_child("section") {
									if let Some(node) = node.get_mut_child("p") {
										if let Some(node) = node.get_mut_child("a") {
											node.attributes.insert("href".to_string(), "https://www.ecfr.gov".to_string());
										}
									}
								}
							}
						}
						Err(e) => {
							eprintln!("Failed to parse XML file {}: {}", xml_file, e);
						}
					}
				}
				Err(e) => {
					eprintln!("Failed to open file {}: {}", xml_file, e);
				}
			}
		}
	}


	Ok(())
}
