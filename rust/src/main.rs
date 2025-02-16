mod scripts;
use std::env;

fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Collect the arguments passed to the script
    let args: Vec<String> = env::args().collect();

    // Get the first argument
    let argument = &args[1];

    // Match the argument and perform actions based on its value
    match argument.as_str() {
        "flat" => scripts::parse_to_flatlist::run()?,
        "nested" => scripts::parse_to_nested::run()?,
        "full" => scripts::parse_to_full_title::run()?,
        _ => unreachable!("Invalid argument provided"),
    }

	Ok(())
}