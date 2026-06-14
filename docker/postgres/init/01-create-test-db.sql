-- Runs only on first initialization of the Postgres data volume.
-- Creates the dedicated test database so running tests never touches dev data.
CREATE DATABASE laravel_test;
