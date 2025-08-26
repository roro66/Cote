#!/bin/sh
set -e

# This script runs inside the postgres container on first init
# Create a testing database owned by admin if it doesn't exist

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" -d postgres <<-EOSQL
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_database WHERE datname = 'testing') THEN
            PERFORM 1;
        END IF;
    END $$;
EOSQL

# Try to create, ignore error if exists
psql -v ON_ERROR_STOP=0 --username "$POSTGRES_USER" -d postgres <<-EOSQL
    CREATE DATABASE testing OWNER admin TEMPLATE template0 ENCODING 'UTF8';
EOSQL
