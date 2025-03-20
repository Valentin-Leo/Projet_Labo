import time
import serial
import serial.tools.list_ports

# Seule la carte "PROF" est acceptée
valid_cards = {
    "11002D7B7F": "PROF"
}

def detect_reader():
    ports = serial.tools.list_ports.comports()
    for port in ports:
        if "ACM" in port.device or "USB" in port.device:
            return port.device
    return None

def ouvrir_armoire():
    print("🗄  Armoire ouverte !")
    time.sleep(2)
    print("🗄  Armoire refermée.")

def main():
    port = detect_reader()
    if not port:
        print("⚠️ Aucun lecteur détecté.")
        return

    print(f"📌 Lecteur Armoire détecté sur {port}")
    try:
        ser = serial.Serial(port, 9600, timeout=1)
        print("✅ En attente de badge (Armoire)...")

        while True:
            raw_data = ser.readline()
            if len(raw_data) >= 9 and raw_data[0] == 0x42:
                card_bytes = raw_data[4:9]
                card_hex = card_bytes.hex().upper()

                print(f"🎫 Armoire - Carte détectée : {card_hex}")
                if card_hex in valid_cards:
                    user_type = valid_cards[card_hex]
                    print(f"👉 Accès autorisé : {user_type}")
                    ouvrir_armoire()
                else:
                    print("❌ Accès refusé. Carte inconnue.")
    except serial.SerialException:
        print("❌ Erreur d'accès au port.")
    except KeyboardInterrupt:
        print("\n🔌 Arrêt du script Armoire.")

if __name__ == "__main__":
    main()
