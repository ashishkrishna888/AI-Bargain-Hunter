import os
import pandas as pd
import json
import re

# Configuration
csv_path = r"C:\xampp\htdocs\AI bargain hunter\laptops.csv"
json_path = r"C:\xampp\htdocs\AI bargain hunter\products.json"
category = "laptops"

if not os.path.exists(csv_path):
    print(f"Error: CSV file not found at {csv_path}")
else:
    print(f"CSV file found at {csv_path}, processing...")

    # Load the CSV
    df = pd.read_csv(csv_path)

    # Combine brand_name and model into a single 'name' field
    df['name'] = df['brand_name'] + ' ' + df['model']

    # Clean price (ensure it's an integer)
    df['price'] = df['price'].astype(int)

    # Use rating as-is (already on 0-5 scale)
    df['rating'] = df['rating'].fillna(4.0)  # Default to 4.0 if missing

    # Add static fields
    df['category'] = category
    df['link'] = '#'
    df['year'] = 2024

    # Create a specs field (Processor, RAM, Storage, Display)
    df['specs'] = df.apply(
        lambda row: f"{row['processor']}, {row['ram_num']} GB RAM, {row['memory_size']} GB {row['memory_type']}, {row['display_size']}-inch Display",
        axis=1
    )

    # Clean and capitalize the name field
    df['name'] = df['name'].apply(lambda x: re.sub(r'\s*\([^)]+\)', '', str(x)))  # Remove text in parentheses
    df['name'] = df['name'].apply(lambda x: re.sub(r'\b(Laptop|Touch|Ee|Un\.\w+|Gaming 3 \w+|\d{2}[A-Za-z0-9]+)\b', '', str(x), flags=re.IGNORECASE))  # Remove model codes and unnecessary details
    df['name'] = df['name'].str.strip().str.title()

    # Remove duplicates based on name
    df = df.drop_duplicates(subset=['name'])

    # Select relevant columns
    df = df[['name', 'price', 'rating', 'category', 'specs', 'year', 'link']]

    # Load existing products
    if os.path.exists(json_path):
        with open(json_path, 'r') as f:
            products = json.load(f)
    else:
        products = []

    # Append new data
    products.extend(df.to_dict('records'))

    # Save updated JSON
    with open(json_path, 'w') as f:
        json.dump(products, f, indent=4)

    print(f"Updated JSON file saved to {json_path}")