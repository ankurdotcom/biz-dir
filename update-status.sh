#!/bin/bash

# Path to status.json
STATUS_FILE="./status.json"

# Timestamp
DATE=$(date +"%Y-%m-%d")

# Define modules and their corresponding prompt files
declare -A MODULES=(
  ["01"]="01_Project_Overview.txt"
  ["02"]="02_Database_Schema.txt"
  ["03"]="03_User_Auth_and_Roles.txt"
  ["04"]="04_Business_Listing_Module.txt"
  ["05"]="05_Review_and_Rating_System.txt"
  ["06"]="06_Tag_Cloud_Engine.txt"
  ["07"]="07_Search_and_Filter.txt"
  ["08"]="08_Moderation_Workflow.txt"
  ["09"]="09_Monetization_Module.txt"
  ["10"]="10_Structured_Data_SEO.txt"
  ["11"]="11_UX_and_UI_Guidelines.txt"
  ["12"]="12_UAT_Checklist.txt"
)

# Start building JSON
echo "{" > $STATUS_FILE
echo "  \"project\": \"Community-Driven Business Directory Platform\"," >> $STATUS_FILE
echo "  \"lastUpdated\": \"$DATE\"," >> $STATUS_FILE
echo "  \"modules\": [" >> $STATUS_FILE

# Loop through modules
for ID in "${!MODULES[@]}"; do
  FILE="prompt/${MODULES[$ID]}"
  STATUS="pending"
  VALIDATED=false
  NOTES=""

  # Check if output file exists (e.g., Claude-generated .md or .json)
  if grep -q "### Claude Output" "$FILE" 2>/dev/null; then
    STATUS="complete"
  fi

  # Check for validation marker
  if grep -q "✅ Validated" "$FILE" 2>/dev/null; then
    VALIDATED=true
  fi

  # Write module entry
  echo "    {" >> $STATUS_FILE
  echo "      \"id\": \"$ID\"," >> $STATUS_FILE
  echo "      \"name\": \"${MODULES[$ID]%.txt}\"," >> $STATUS_FILE
  echo "      \"file\": \"$FILE\"," >> $STATUS_FILE
  echo "      \"status\": \"$STATUS\"," >> $STATUS_FILE
  echo "      \"validated\": $VALIDATED," >> $STATUS_FILE
  echo "      \"notes\": \"$NOTES\"" >> $STATUS_FILE
  echo "    }," >> $STATUS_FILE
done

# Trim trailing comma and close JSON
sed -i '$ s/},/}/' $STATUS_FILE
echo "  ]" >> $STATUS_FILE
echo "}" >> $STATUS_FILE

echo "✅ status.json updated on $DATE"
