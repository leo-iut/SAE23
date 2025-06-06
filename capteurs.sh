#!/bin/bash

# --- Configuration ---
# Your MQTT topics, one for each room's sensor data.
MQTT_TOPICS=(
    "AM107/by-room/B106/data"
    "AM107/by-room/B110/data"
    "AM107/by-room/E003/data"
    "AM107/by-room/E104/data"
)

# MQTT Broker connection details
MQTT_BROKER="mqtt.iut-blagnac.fr"
MQTT_PORT="1883"

# MySQL Database connection details
DB_USER="teyssedre"
DB_PASS="leo"
DB_NAME="sae23" # Ensure this matches your database name

# Path to MySQL client (adjust if needed, /usr/usr/bin/mysql is common on Linux, /opt/lampp/bin/mysql for XAMPP)
MYSQL_CLIENT="/opt/lampp/bin/mysql"

# --- Main Script Logic ---

echo "Starting MQTT subscriptions for sensor data..."
echo "Connecting to $MQTT_BROKER:$MQTT_PORT"

# Loop through each topic and start a background process for subscription.
# Each process will read messages and pipe them to the processing function.
for TOPIC in "${MQTT_TOPICS[@]}"; do
    mosquitto_sub -h "$MQTT_BROKER" -p "$MQTT_PORT" -t "$TOPIC" | while read -r PAYLOAD; do
        # Extract data using jq
        # Note: jq outputs numbers as numbers and strings as strings.
        # The 'Mesure.valeur' column in your database schema is INT(11).
        # If the sensor data (temperature, humidity, co2) can be floating-point numbers,
        # they will be truncated during insertion or cause errors.
        # Consider changing 'valeur' to FLOAT or DECIMAL in your 'Mesure' table
        # if non-integer values are expected.
        TEMP=$(echo "$PAYLOAD" | jq '.[0].temperature')
        HUMID=$(echo "$PAYLOAD" | jq '.[0].humidity')
        CO2=$(echo "$PAYLOAD" | jq '.[0].co2')
        ROOM=$(echo "$PAYLOAD" | jq -r '.[1].room') # -r for raw string output

        DATETIME=$(date "+%Y-%m-%d %H:%M:%S")

        # Define sensor types and their units for easy iteration
        declare -A SENSORS=(
            ["temperature"]="$TEMP"
            ["humidity"]="$HUMID"
            ["co2"]="$CO2"
        )
        declare -A UNITS=(
            ["temperature"]="°C"
            ["humidity"]="%"
            ["co2"]="ppm"
        )

        echo "Received message for room '$ROOM' at $DATETIME"

        # --- Database Insertion Pre-checks (Salle) ---
        # The 'Capteur' table has a foreign key 'nom_salle' referencing 'Salle.nom_salle'.
        # To ensure referential integrity, we must confirm the 'Salle' entry exists first.
        # The 'Salle' table (according to your schema) requires 'capacite', 'type', and 'id_batiment'.
        # As these values are NOT provided by the MQTT payload, we use placeholder values.
        # It is highly recommended that you either:
        # 1. Pre-populate your 'Salle' table with accurate data.
        # 2. Modify your 'Salle' table to allow NULLs or define default values for these columns.
        # 3. Enhance your MQTT payload to include this information.
        SALLE_CAPACITE=0       # Placeholder: Default capacity
        SALLE_TYPE="Undefined" # Placeholder: Default room type
        SALLE_ID_BATIMENT=1    # Placeholder: Assuming Batiment ID 1 exists.
                               # Ensure a 'Batiment' with this ID is present,
                               # or adjust to an appropriate existing ID.

        if [[ -n "$ROOM" && "$ROOM" != "null" ]]; then
            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT IGNORE INTO Salle (nom_salle, capacite, type, id_batiment)
                VALUES ('$ROOM', $SALLE_CAPACITE, '$SALLE_TYPE', $SALLE_ID_BATIMENT);
            "
            if [ $? -eq 0 ]; then
                echo "  Salle '$ROOM' handled (inserted if new, with placeholder data)."
            else
                echo "  Error handling Salle '$ROOM'. Check if foreign key constraint for 'id_batiment' is met (i.e., Batiment ID 1 exists)."
            fi
        else
            echo "  Warning: Room name is missing or null in payload. Cannot process sensor data for this entry."
            continue # Skip to the next payload if room name is invalid
        fi


        # Process each sensor data point (temperature, humidity, co2)
        for TYPE in "${!SENSORS[@]}"; do
            SENSOR_NAME="${TYPE}_${ROOM}" # e.g., 'temperature_B106'
            VALUE="${SENSORS[$TYPE]}"
            UNIT="${UNITS[$TYPE]}"

            # Skip if value is empty or "null" (as returned by jq for missing fields)
            if [[ -z "$VALUE" || "$VALUE" == "null" ]]; then
                echo "  Warning: '$TYPE' value for room '$ROOM' is missing or null. Skipping this sensor reading."
                continue
            fi

            # --- Database Insertion (Capteur) ---
            # 1. Insert/Update Capteur (sensor definition)
            #    'INSERT IGNORE' prevents errors if the sensor already exists,
            #    making the script idempotent for sensor definitions.
            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT IGNORE INTO Capteur (nom_capteur, type_capteur, Unite, nom_salle)
                VALUES ('$SENSOR_NAME', '$TYPE', '$UNIT', '$ROOM');
            "
            if [ $? -eq 0 ]; then
                echo "  Capteur '$SENSOR_NAME' (Type: $TYPE, Room: $ROOM) handled."
            else
                echo "  Error handling Capteur '$SENSOR_NAME'. Make sure 'nom_salle' foreign key is valid."
            fi

            # --- Database Insertion (Mesure) ---
            # 2. Insert Mesure (actual sensor reading)
            #    The 'id_mesure' column is an auto-incrementing primary key, so we don't insert it.
            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT INTO Mesure (nom_capteur, valeur, date_heure)
                VALUES ('$SENSOR_NAME', '$VALUE', '$DATETIME');
            "
            if [ $? -eq 0 ]; then
                echo "  Mesure inserted for '$SENSOR_NAME': Value $VALUE at $DATETIME."
            else
                echo "  Error inserting Mesure for '$SENSOR_NAME'. Check 'valeur' data type (INT) compatibility."
            fi
        done
        echo "---" # Separator for readability between different payload processing
    done & # Run this mosquitto_sub and its processing in the background
done

echo "All MQTT subscriptions are active. Press Ctrl+C to stop the script."
wait # Keep the main script running to allow background jobs to continue
