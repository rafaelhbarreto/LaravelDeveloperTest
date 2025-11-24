import './bootstrap';
import { createApp } from 'vue';

// Auto-mount Vue components
document.addEventListener('DOMContentLoaded', () => {
    // Find all elements with data-vue-component attribute
    const vueElements = document.querySelectorAll('[data-vue-component]');

    vueElements.forEach(el => {
        const componentName = el.dataset.vueComponent;
        const props = el.dataset.props ? JSON.parse(el.dataset.props) : {};

        // Dynamically import and mount the component
        import(`./Pages/${componentName}.vue`).then(module => {
            createApp(module.default, props).mount(el);
        }).catch(error => {
            console.error(`Failed to load component: ${componentName}`, error);
        });
    });
});
