import os
import pandas as pd
import json
import re
import logging
from datetime import datetime

# Configuration
csv_path = r"C:\xampp\htdocs\AI bargain hunter\earphones.csv"
json_path = r"C:\xampp\htdocs\AI bargain hunter\products.json"
category = "earphones"

def clean_price(price_str):
    """Clean price strings with ₹ symbol, commas, and special characters"""
    if isinstance(price_str, str):
        # Remove ₹, commas, and any other non-numeric characters except decimal point
        cleaned = re.sub(r'[^\d.]', '', price_str)
        return int(float(cleaned)) if cleaned else 0
    return int(price_str) if pd.notna(price_str) else 0

def main():
    # Setup logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        filename='earphones_import.log'
    )

    if not os.path.exists(csv_path):
        logging.error(f"CSV file not found at {csv_path}")
        print(f"Error: CSV file not found at {csv_path}")
        return

    try:
        # Load the CSV with explicit encoding
        df = pd.read_csv(csv_path, encoding='utf-8')
        logging.info(f"Successfully loaded CSV with {len(df)} records")

        # Create product name
        df['product_name'] = (df['company'] + ' ' + df['name']).str.title()
        df['product_name'] = df['product_name'].apply(lambda x: re.sub(r'\s*\([^)]+\)', '', str(x)))

        # Clean prices
        df['offer_price'] = df['offer_price'].apply(clean_price)
        df['real_price'] = df['real_price'].apply(clean_price)
        
        # Calculate discount percentage
        df['discount_pct'] = round((df['real_price'] - df['offer_price']) / df['real_price'] * 100)
        
        # Use offer price as our price
        df['price'] = df['offer_price']
        
        # Clean ratings (handle thousands separator in people_review)
        df['people_review'] = df['people_review'].str.replace(',', '').fillna('0').astype(int)
        df['rating'] = pd.to_numeric(df['ratings'], errors='coerce').fillna(4.0).clip(0, 5)

        # Add additional specs
        df['specs'] = df['type'] + ', ' + df['color']

        # Add static fields
        df['category'] = category
        df['link'] = '#'
        df['year'] = datetime.now().year
        df['source'] = 'Flipkart'

        # Select final columns
        final_products = df[[
            'product_name', 'price', 'rating', 'category', 'specs', 
            'year', 'link', 'real_price', 'discount_pct', 'people_review', 'source'
        ]].rename(columns={'product_name': 'name'})

        # Load existing products
        if os.path.exists(json_path):
            try:
                with open(json_path, 'r', encoding='utf-8') as f:
                    products = json.load(f)
            except (json.JSONDecodeError, UnicodeDecodeError):
                logging.warning("products.json was corrupted, starting fresh")
                products = []
        else:
            products = []

        # Append new data (avoid duplicates by name and price)
        existing_products = {(p['name'], p['price']) for p in products}
        new_products = [
            p for p in final_products.to_dict('records') 
            if (p['name'], p['price']) not in existing_products
        ]

        products.extend(new_products)

        # Save updated JSON
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump(products, f, indent=4, ensure_ascii=False)

        logging.info(f"Added {len(new_products)} new {category} products. Total now: {len(products)}")
        print(f"Success! Added {len(new_products)} new earphone products to the database.")

    except Exception as e:
        logging.error(f"Error processing data: {str(e)}", exc_info=True)
        print(f"Error: {str(e)}")

if __name__ == "__main__":
    main()