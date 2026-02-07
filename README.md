# Iseki Viewer - Lightweight Image Slideshow System

## Overview

**Iseki Viewer** is a standalone, lightweight PHP application designed for managing and displaying image slideshows. It provides a simple web interface to organize images into category folders, upload images in bulk, and view them in a dynamic, auto-playing slideshow format.

Built with performance and simplicity in mind, it requires no external frameworks or complex databases, making it highly portable and easy to deploy on any standard PHP environment.

## Key Features

### 📁 Slideshow Management
*   **Folder-Based Organization**: Group your slides into logical folders (e.g., "Quality Controls", "Safety Briefs", "Production Metrics").
*   **Dynamic Creation**: Create new slideshow categories directly from the web interface.
*   **Cleanup Tools**: Easily delete entire slideshow categories and their associated images when no longer needed.

### 📤 Bulk Upload System
*   **Multi-File Selection**: Upload dozens of images at once into a specific folder.
*   **Instant Preview**: Review currently uploaded images within a folder before starting the viewer.
*   **Format Support**: Handles standard web formats including `.jpg`, `.jpeg`, `.png`, and `.gif`.

### 📺 Advanced Slideshow Viewer
*   **Auto-Play Engine**: Smooth fade transitions with configurable durations (default 3 seconds).
*   **Intelligent Sorting**: Uses "Natural Sorting" (`strnatcmp`) to ensure `slide2.jpg` comes before `slide10.jpg`.
*   **UI Intelligence**: Navigation controls and settings panels automatically hide after 10 seconds of inactivity to provide a clean, "Kiosk-style" viewing experience.
*   **Manual Overrides**: Pause/Resume controls and manual Next/Previous navigation.
*   **Auto-Refresh**: Automatically refreshes every 30 minutes to ensure the latest content is displayed (ideal for persistent dashboard displays).

## Technology Stack

### Backend
*   **Language**: Vanilla PHP (8.x recommended)
*   **Storage**: Filesystem-based (located in the `slides/` directory)
*   **Dependencies**: None. Purely standalone.

### Frontend
*   **Structure**: Semantic HTML5
*   **Styling**: Vanilla CSS3 (Responsive layout)
*   **Interactivity**: Vanilla JavaScript (ES6+)

## Project Structure

```text
iseki_viewer/
├── slides/           # Storage for all slideshow folders and images
├── index.php         # Main dashboard and folder management
├── viewer.php        # The core slideshow engine
├── upload.php        # Image upload and preview interface
├── logo-iseki.png    # Brand asset
└── file_manager.php  # Internal utilities
```

## Installation & Setup

1.  **Deployment**
    Copy the `iseki_viewer` folder to your web server's root directory (e.g., `C:\xampp\htdocs\` or `/var/www/html/`).

2.  **Permissions**
    Ensure the `slides/` directory is writable by the web server process:
    ```bash
    chmod -R 775 slides/
    ```

3.  **Access**
    Open your browser and navigate to `http://localhost/iseki_viewer/`.

## Usage Tips

*   **For Dashboards**: Open a slideshow in full-screen mode. The UI will hide itself, and the page will auto-refresh periodically to pick up any new image uploads.
*   **Naming Convention**: For the best viewing order, name your files using a prefix and number (e.g., `image_1.jpg`, `image_2.jpg`).

## License

This project is proprietary.
