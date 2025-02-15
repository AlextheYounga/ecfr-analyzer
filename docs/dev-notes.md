# ECFR Developer Notes

## Approach 1 - Scrape Entire eCFR 
My first approach to designing this app was to approach it in a similar manner to my US Federal Code project, but I underestimated the sheer size of the documents

## Approach 2 - Use eCFR API for All Actions
This is not ideal, as the API has a tendency to timeout occassionally, and can be extremely slow at times. 

## Approach 3 - Hybrid Approach
Use a mix of API functions and scraping large chunks from the site/api


## Must Haves
- Word analyzer by agency
- Historical changes over time 
- Conversion of XML documents into either plain text or markdown in order 

## Nice to Haves
- Full Git history of each section (this is technically already handled by ecfr.gov, [example](https://www.ecfr.gov/compare/2022-05-04/to/2022-05-03/title-1/chapter-I/subchapter-D/part-12))


Get the historical 