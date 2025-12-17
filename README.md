# Arma Reforger Workshop Parser

A lightweight PHP tool for extracting mod information from the **Arma Reforger Workshop** pages and generating ready-to-use mod entries for server configuration.

## Features
- Parses **mod ID**, **name**, and **version** directly from Arma Reforger Workshop pages  
- Automatically detects and processes **Dependencies**
- Recursively loads all dependency pages and extracts their data
- Outputs clean, formatted JSON blocks ready for server configs
- Preserves correct order: main mod first, dependencies after
- No trailing commas in output
- One-click **Copy to clipboard**
- Temporary files are automatically cleaned up
- Single-file implementation (PHP + HTML)
- Dark UI optimized for technical usage

## Supported URLs
Only official Arma Reforger Workshop pages are accepted:

## Output Format
```json
{
    "modId": "66BE6A50996A9E77",
    "name": "Everon RP",
    "version": "1.0.28"
},
{
    "modId": "5D2D1436D1FA5A13",
    "name": "Shop System",
    "version": "1.4.2"
}
```
## Requirements
- PHP 7.4+
- allow_url_fopen enabled
- DOM extension enabled (default in most PHP installs)

## Usage
- Upload the folder `/parsing-arma/` containing `index.php` to the root of your server
- Open the tool in your browser using `https://your-domain.com/parsing-arma/`
- Paste an Arma Reforger Workshop mod URL
- Click Process page
- Copy the generated JSON
