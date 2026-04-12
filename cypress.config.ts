import { defineConfig } from 'cypress';

export default defineConfig({
    e2e: {
        baseUrl: 'http://localhost:8000',
        supportFile: false,
        specPattern: 'cypress/e2e/**/*.cy.{js,ts}',
        viewportWidth: 1280,
        viewportHeight: 720,
        defaultCommandTimeout: 10000,
    },
});
