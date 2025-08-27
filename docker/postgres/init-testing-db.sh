#!/bin/sh
set -e

# This script runs inside the postgres container on first init
# Create a testing database owned by admin; ignore error if it already exists.

# Try to create, ignore error if exists
psql -v ON_ERROR_STOP=0 --username "$POSTGRES_USER" -d postgres <<-'EOSQL'
    CREATE DATABASE testing OWNER admin TEMPLATE template0 ENCODING 'UTF8';
EOSQL
