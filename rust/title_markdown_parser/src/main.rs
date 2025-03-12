mod scripts;
use std::env;
use std::path::Path;

fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Collect the arguments passed to the script
    let args: Vec<String> = env::args().collect();

	if args.len() < 4 {
		eprintln!("Usage: title_markdown_parser <subroutine> <structure_folder> <input_folder> <output_folder>");
		std::process::exit(1);
	}

	for arg in &args {
		if arg == &args[1] { continue; }
		if !Path::new(arg).exists() {
			eprintln!("{} does not exist", arg);
			std::process::exit(1);
		}
	}

    // Get the first argument
    let subroutine = &args[1];
	let structure_folder = &args[2];
	let input_folder = &args[3];
	let output_folder = &args[4];


    // Match the argument and perform actions based on its value
    match subroutine.as_str() {
        "flat" => scripts::parse_to_flatlist::run(input_folder, output_folder, structure_folder),
        "nested" => scripts::parse_to_nested::run(input_folder, output_folder, structure_folder),
        "full" => scripts::parse_to_full_title::run(input_folder, output_folder, structure_folder),
        _ => unreachable!("Invalid argument provided"),
    }

	Ok(())
}