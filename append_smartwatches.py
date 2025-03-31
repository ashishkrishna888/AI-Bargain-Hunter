import os
import pandas as pd
import json
import re
from datetime import datetime

# Configuration
csv_path = r"C:\xampp\htdocs\AI bargain hunter\smart_watch.csv"
json_path = r"C:\xampp\htdocs\AI bargain hunter\products.json"
category = "smartwatches"

if not os.path.exists(csv_path):
    print(f"Error: CSV file not found at {csv_path}")
else:
    print(f"CSV file found at {csv_path}, processing...")

    # Load the CSV
    df = pd.read_csv(csv_path)

    # Clean and transform data
    df['name'] = df['name'].str.strip().apply(lambda x: re.sub(r'\s*\([^)]+\)', '', str(x)).title())
    df['price'] = df['price'].astype(int)
    df['rating'] = pd.to_numeric(df['rating'], errors='coerce').fillna(4.0).clip(0, 5)

    # Create a comprehensive specs string
    def create_specs(row):
        specs = []
        
        # Display size
        if pd.notna(row['display_size_inches']):
            specs.append(f"{row['display_size_inches']}\" display")
        
        # Bluetooth
        if pd.notna(row['Bluetooth']) and row['Bluetooth']:
            specs.append(str(row['Bluetooth']))
        
        # Features
        features = {
            'water_resistance': "Water resistant",
            'heart_rate_monitor': "Heart rate monitor",
            'Blood_Pressure_monitor': "Blood pressure monitor",
            'sleep_monitor': "Sleep tracking"
        }
        
        for col, desc in features.items():
            if col in row and row[col]:
                specs.append(desc)
        
        # Battery
        if pd.notna(row['battery']) and row['battery']:
            specs.append(f"{row['battery']} battery")
        
        # Convert all specs to strings and join
        return ', '.join([str(s) for s in specs if s])

    df['specs'] = df.apply(create_specs, axis=1)

    # Add static fields
    df['category'] = category
    df['link'] = '#'
    df['year'] = datetime.now().year

    # Select only relevant columns
    df = df[['name', 'price', 'rating', 'category', 'specs', 'year', 'link']]

    # Load existing products
    if os.path.exists(json_path):
        try:
            with open(json_path, 'r') as f:
                products = json.load(f)
        except json.JSONDecodeError:
            print("Warning: products.json was corrupted, starting fresh")
            products = []
    else:
        products = []

    # Append new data (avoid duplicates)
    existing_names = {p['name'] for p in products}
    new_products = [p for p in df.to_dict('records') if p['name'] not in existing_names]

    products.extend(new_products)

    # Save updated JSON
    with open(json_path, 'w') as f:
        json.dump(products, f, indent=4)

    print(f"Added {len(new_products)} new smartwatches. Total products now: {len(products)}")