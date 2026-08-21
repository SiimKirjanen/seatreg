#!/bin/bash

# Removes everything the WordPress.org plugin directory does not need.
# Run it inside a fresh clone, then copy what is left into the SVN folder.

# List of files and directories to remove
FILES_TO_REMOVE=(
    ".github"
    ".git"
    ".gitignore"
    ".vscode"
    ".claude"
    ".mcp.json"
    ".wp-env.json"
    ".playwright-mcp"
    "CLAUDE.md"
    "README.md"
    "gulpfile.js"
    "package.json"
    "package-lock.json"
    "playwright.config.js"
    "playwright"
    "playwright-report"
    "test-results"
    "tests"
    "node_modules"
    "cleanup.sh"
)

# Prompt the user for confirmation
read -p "Do you want to continue with the cleanup? (y/n): " CONFIRMATION

if [[ "$CONFIRMATION" != "y" ]]; then
  echo "Cleanup aborted."
  exit 1
fi

# Remove each file and directory
for FILE in "${FILES_TO_REMOVE[@]}"; do
  if [ -e "$FILE" ]; then
    rm -rf "$FILE"
    echo "Removed $FILE"
  else
    echo "$FILE does not exist"
  fi
done
