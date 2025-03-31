# AI Bargain Hunter 🛍️

An AI-powered shopping assistant that helps users find the best deals on electronics (mobiles, laptops, earphones, and more) in India. Built as a school project with a stunning glassmorphism UI, real-time deal fetching, and a dynamic chat interface.

![AI Bargain Hunter Screenshot](assets/Screenshot1.png)

## ✨ Features
- **Smart Search**: Search for products with price filters (e.g., "laptops under 50000").
- **Glassmorphism UI**: Beautiful design with day/night themes and animated background.
- **Developer Section**: Hover over the top-right ellipses to see developer details with a futuristic animation.
- **Real-Time Deals**: Fetches deals dynamically using PHP and Python.
- **Responsive Design**: Works seamlessly on desktop and mobile.

## 📸 Screenshots
| Chat Interface | Developer Section |
|----------------|-------------------|
| ![Chat Interface](assets/Screenshot1.png) | ![Developer Section](assets/Screenshot3.png) |

## 🎥 Demo
Watch a quick demo of the app in action:  
[Demo Video](assets/demo.mp4)

## 🛠️ Tech Stack
- **Frontend**: HTML, Tailwind CSS, JavaScript
- **Backend**: PHP (for API), Python (for data preprocessing)
- **Data**: CSV files processed into JSON
- **Styling**: Glassmorphism with day/night themes
- **Dependencies**: FontAwesome, Google Fonts (Poppins)

## 🚀 How to Run
1. **Prerequisites**:
   - Install [XAMPP](https://www.apachefriends.org/index.html) to run the PHP server.
   - Install Python 3.x for data preprocessing.
2. **Setup**:
   - Clone this repository:
   - - Move the project folder to `C:\xampp\htdocs\AI-Bargain-Hunter`.
   - Start XAMPP and ensure Apache is running.
3. **Preprocess Data**:
   - Run the Python scripts to generate `products.json`:
4. **Run the App**:
   - Open your browser and go to `http://localhost/AI-Bargain-Hunter/`.
   - Start searching for products (e.g., "gaming laptops", "earphones")!

## 📝 Project Structure
AI-Bargain-Hunter/
│
├── index.html          # Main HTML file
├── api.php             # Backend API for handling queries
├── append_earphones.py # Python script for earphones data
├── append_laptops.py   # Python script for laptops data
├── append_smartwatches.py # Python script for smartwatches data
├── earphones.csv       # CSV file for earphones
├── laptops.csv         # CSV file for laptops
├── smartwatches.csv    # CSV file for smartwatches (if applicable)
├── products.json       # JSON file with product data
├── assets/             # Screenshots
└── README.md           # Project documentation

## 📜 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙌 Acknowledgments
- Inspired by modern UI trends like glassmorphism and neon aesthetics.
