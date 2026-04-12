/// <reference types="cypress" />

describe('Finance Module - E2E Tests', () => {
    // These tests require a logged-in user session.
    // In a real CI environment, you would seed the DB and use a test user.

    describe('Dashboard Access (Unauthenticated)', () => {
        it('redirects to login when accessing dashboard without auth', () => {
            cy.visit('/dashboard');
            cy.url().should('include', '/login');
        });

        it('redirects to login when accessing expenses without auth', () => {
            cy.visit('/expenses');
            cy.url().should('include', '/login');
        });

        it('redirects to login when accessing incomes without auth', () => {
            cy.visit('/incomes');
            cy.url().should('include', '/login');
        });

        it('redirects to login when accessing settings without auth', () => {
            cy.visit('/settings');
            cy.url().should('include', '/login');
        });
    });

    describe('Protected Routes Guard', () => {
        it('redirects to login when accessing investments without auth', () => {
            cy.visit('/investments');
            cy.url().should('include', '/login');
        });

        it('redirects to login when accessing portfolio without auth', () => {
            cy.visit('/portfolio');
            cy.url().should('include', '/login');
        });
    });

    describe('API Endpoints Guard', () => {
        it('returns 401 for unauthenticated API account access', () => {
            cy.request({
                url: '/api/accounts',
                failOnStatusCode: false
            }).then((response) => {
                // Should redirect to login (302) or return 401
                expect([302, 401, 419]).to.include(response.status);
            });
        });
    });
});
