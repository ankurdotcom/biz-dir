#!/bin/bash

# Usage: ./validate-module.sh 03
# Where "03" is the module ID

MODULE_ID="$1"
STATUS_FILE="./status.json"

if [ -z "$MODULE_ID" ]; then
  echo "❌ Please provide a module ID (e.g., 03)"
  exit 1
fi

# Use jq to update the module entry
jq --arg id "$MODULE_ID" '
  .modules |= map(
    if .id == $id then
      .status = "complete" |
      .validated = true |
      .notes = "✅ Validated manually on '"$(date +"%Y-%m-%d %H:%M")"'"
    else
      .
    end
  )
' "$STATUS_FILE" > tmp.json && mv tmp.json "$STATUS_FILE"

echo "✅ Module $MODULE_ID marked as validated in status.json"
