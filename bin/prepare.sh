#!/bin/bash

php -dphar.readonly=0 utils/make-phar.php

# Create a .env file
echo "Saving PANTHEON_WPVULNDB_API_TOKEN to .env"
echo "PANTHEON_WPVULNDB_API_TOKEN=$PANTHEON_WPVULNDB_API_TOKEN" >> .env