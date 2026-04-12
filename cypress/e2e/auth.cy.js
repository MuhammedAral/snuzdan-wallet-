/// <reference types="cypress" />

describe('Auth Module - E2E Tests', () => {
    beforeEach(() => {
        cy.visit('/');
    });

    it('shows the welcome page with login and register links', () => {
        cy.contains('Giriş').should('be.visible');
        cy.contains('Kayıt').should('be.visible');
    });

    it('navigates to login page', () => {
        cy.visit('/login');
        cy.url().should('include', '/login');
        cy.get('input[type="email"]').should('exist');
        cy.get('input[type="password"]').should('exist');
    });

    it('navigates to register page', () => {
        cy.visit('/register');
        cy.url().should('include', '/register');
        cy.get('input[type="email"]').should('exist');
    });

    it('shows validation errors on empty login submit', () => {
        cy.visit('/login');
        cy.get('form').submit();
        // Should stay on login page and show errors
        cy.url().should('include', '/login');
    });

    it('shows validation errors on empty register submit', () => {
        cy.visit('/register');
        cy.get('form').submit();
        // Should stay on register page and show errors
        cy.url().should('include', '/register');
    });
});
