# ChatGPT-Article-Writer-Plugin
Welcome to the ChatGPT Article Writer plugin repository! 🎉 This WordPress plugin integrates OpenAI's 
ChatGPT API to generate high-quality articles for your website. It's the ultimate tool for content 
creators who want to save time and automate their blogging process.

Features 🚀
AI-Powered Content Creation: Automatically generate articles for any public post type using OpenAI's GPT models.
Custom Prompts: Tailor prompts to your specific needs for precise and engaging content.
Secure API Integration: Easily connect and validate your OpenAI API key.
User-Friendly Admin Panel: Manage settings and prompts via an intuitive interface.
Gutenberg Compatibility: Enhance your editor experience with AI-generated content.
AJAX-Powered Workflow: Enjoy smooth, real-time operations for article generation.

Installation 🔧
Clone the repository:
git clone https://github.com/dujonpratt/chatgpt-article-writer.git
Upload the plugin folder to your WordPress installation directory:

/wp-content/plugins/

Activate the plugin via the Plugins page in your WordPress admin dashboard.
Go to ChatGPT Writer in the admin menu to configure settings.

Usage 📝
Set Your API Key
Navigate to the Settings page.
Enter your OpenAI API key and save.
Add Prompts

Choose a post type and provide a prompt.
Prompts can be anything from "Write a tech blog on AI advancements" to "Create a product description for a gadget."

Generate Articles
Select prompts and click Generate Articles to instantly create posts.
Articles are automatically published or saved as drafts based on your WordPress settings.

Code Highlights 💻
API Integration:
The plugin uses wp_remote_post to connect securely with OpenAI’s API.

$response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_tokens' => 500,
        'temperature' => 0.7,
    ]),
]);

AJAX Workflow:
The plugin utilizes WordPress AJAX hooks for real-time interactions between the admin panel and backend.

Gutenberg Enhancements:
Custom scripts and styles are enqueued to support AI integration within the block editor.

Requirements 📋
WordPress 5.5 or later
PHP 7.4 or later
OpenAI API Key

Roadmap 🌟
Multi-language support for article generation.
AI style and tone customization for diverse content needs.
Advanced analytics for monitoring API usage and performance.

Contributing 👨‍💻
We welcome contributions! To get started:

Fork the repository.
Create a new branch for your feature or bug fix:
git checkout -b feature-name

Commit your changes:
git commit -m "Add new feature"
Push your branch and submit a pull request.

Support & Feedback 💬
Have questions, suggestions, or need help? Open an issue in the repository, and we’ll get back to you ASAP.

Author: Dujon Pratt
Version: 1.0.0
License: GPLv2 or later

Let’s automate content creation with ChatGPT! 🌐✨

