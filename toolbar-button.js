const { registerPlugin } = wp.plugins;
const { ToolbarButton, ToolbarGroup } = wp.components;
const { PluginToolbar } = wp.editPost;
const { Fragment } = wp.element;

const CustomTopToolbar = () => {
    const handleClick = () => {
        alert('Custom Top Toolbar Button Clicked!');
    };

    return (
        <PluginToolbar>
            <ToolbarGroup>
                <ToolbarButton
                    icon="smiley" // Dashicon or custom SVG
                    label="Custom Action"
                    onClick={handleClick}
                />
            </ToolbarGroup>
        </PluginToolbar>
    );
};

// Register the plugin
registerPlugin('custom-top-toolbar-plugin', {
    render: CustomTopToolbar,
    icon: 'smiley',
});
