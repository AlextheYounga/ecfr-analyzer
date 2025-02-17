use std::fs;
use std::path::Path;
use serde_json;
use serde_derive::Serialize;


#[derive(Serialize)]
struct TitleWords {
	title_number: i32,
	section_id: String,
	word_count: usize,
}

fn count_words(text: String) -> usize{
	let result: Vec<&str> = text
	.trim()                // Trim leading/trailing whitespace
	.split_whitespace()    // Split by whitespace (spaces, tabs, etc.)
	.collect();            // Collect the parts into a vector

	return result.len();
}
fn main() {
	let title_numbers = 1..50;
	let markdown_directory = "./storage/app/private/ecfr/current/documents/markdown/flat";
	let mut title_list: Vec<TitleWords> = Vec::new();

	for num in title_numbers {
		let title_directory = format!("{}/title-{}", markdown_directory, num);
		// Check if path exists
		if !Path::new(&title_directory).exists() {
			continue;
		}
		let title_files = fs::read_dir(title_directory).unwrap();

		for file in title_files {
			let file = file.unwrap();
			let file_path = file.path();
			let file_name = file_path.file_name().unwrap().to_str().unwrap();
			let section_id = file_name.replace(".md", "");

			let file_content = fs::read_to_string(file_path).unwrap();
			let word_count = count_words(file_content);
			let title_data = TitleWords {
				title_number: num,
				section_id: section_id,
				word_count: word_count,
			};
			title_list.push(title_data);
		}
	}
	
	// Write data to file
	let output_file = "./storage/app/private/ecfr/word_count.json";
	let output_data = serde_json::to_string(&title_list).unwrap();
	fs::write(output_file, output_data).unwrap();

}