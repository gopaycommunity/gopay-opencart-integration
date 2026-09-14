<?php
# Heading
$_['heading_title']                   = 'OpenCart GoPay gateway';

# Text
$_['text_extension']                  = 'Extensions';
$_['text_edit']                       = 'GoPay payment gateway';
$_['text_success']                    = 'Das GoPay-Zahlungsmodul wurde erfolgreich geändert!';

# GoPay plugin config entry
$_['gopay_enable_disable']            = 'Aktivieren/Deaktivieren';
$_['gopay_inline']                    = 'Inline-Zahlungsgateway';
$_['gopay_title']                     = 'Titel';
$_['gopay_description']               = 'Beschreibung';
$_['gopay_goid']                      = 'GoID';
$_['gopay_client_id']                 = 'Client ID';
$_['gopay_client_secret']             = 'Client Secret';
$_['gopay_test']                      = 'Prüfmodus';
$_['gopay_default_language']          = 'Standardsprache';
$_['gopay_shipping_methods']          = 'Versandmethoden aktivieren';
$_['gopay_countries']                 = 'Länder freischalten';
$_['gopay_simplified_bank']           = 'Auswahl der Bank';
$_['gopay_payment_methods']           = 'GoPay-Zahlungsmethoden aktivieren';
$_['gopay_banks']                     = 'Banken freischalten';
$_['gopay_payment_retry']             = 'Wiederholung der Zahlungsmethode';
$_['gopay_card_token']                = 'Mit gespeicherter Karte bezahlen';

# GoPay plugin config placeholder
$_['gopay_title_placeholder']         = 'Zahlungstitel einfügen...';
$_['gopay_description_placeholder']   = 'Beschreibung einfügen...';
$_['gopay_goid_placeholder']          = 'Geben Sie Ihre GoID ein...';
$_['gopay_client_id_placeholder']     = 'Geben Sie Ihre GoPay-Kunden-ID ein...';
$_['gopay_client_secret_placeholder'] = 'Geben Sie Ihr GoPay Client Secret Token ein...';

# GoPay plugin config tooltip
$_['gopay_enable_disable_tooltip']    = 'GoPay-Zahlungsgateway aktivieren.';
$_['gopay_inline_tooltip']            = 'Das Inline-Zahlungsgateway wird direkt über dem Verkaufsort initiiert.';
$_['gopay_title_tooltip']             = 'Name der Zahlungsmethode, die an der Kasse angezeigt wird.';
$_['gopay_description_tooltip']       = 'Beschreibung der Zahlungsmethode, die an der Kasse angezeigt wird.';
$_['gopay_goid_tooltip']              = 'Geben Sie Ihre eindeutige GoID ein, die Sie in den Einstellungen Ihres GoPay-Kontos finden.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Weitere Informationen</a>.';
$_['gopay_client_id_tooltip']         = 'Geben Sie Ihre eindeutige Kunden-ID ein, die Sie in den Einstellungen Ihres GoPay-Kontos finden.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Weitere Informationen</a>.';
$_['gopay_client_secret_tooltip']     = 'Geben Sie Ihr Kundengeheimnis-Token ein, das Sie in den Einstellungen Ihres GoPay-Kontos finden.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Weitere Informationen</a>.';
$_['gopay_test_tooltip']              = 'Aktivieren Sie den Testmodus des GoPay-Zahlungsgateways.';
$_['gopay_default_language_tooltip']  = 'Die Standardsprache wird verwendet, wenn ein Kunde einer E-Commerce-Website aus einem Land kommt, dessen Sprache nicht unterstützt wird.';
$_['gopay_shipping_methods_tooltip']  = 'Aktivieren Sie das GoPay Zahlungs-Gateway nur für die ausgewählten OpenCart Versandmethoden.';
$_['gopay_countries_tooltip']         = 'Aktivieren Sie das GoPay-Zahlungsgateway nur für die ausgewählten Länder.';
$_['gopay_simplified_bank_tooltip']   = 'Wenn diese Option aktiviert ist, können die Kunden an der Kasse keine bestimmte Bank auswählen. Sie werden in einer "Bankkonto"-Option gruppiert, müssen aber die Bank auswählen, sobald das GoPay-Zahlungsgateway aufgerufen wird.';
$_['gopay_payment_methods_tooltip']   = 'Aktivieren Sie nur die ausgewählten Zahlungsmethoden auf dem GoPay-Zahlungsgateway.';
$_['gopay_banks_tooltip']             = 'Aktivieren Sie nur die ausgewählten Banken auf dem GoPay-Zahlungsgateway.';
$_['gopay_payment_retry_tooltip']     = 'Wenn diese Option aktiviert ist, wird die Wiederholung einer fehlgeschlagenen Zahlung mit der gleichen Zahlungsmethode durchgeführt, die der Kunde bei der Bestellung ausgewählt hat.';
$_['gopay_card_token_tooltip']        = 'Wenn ein Kunde mit Karte bezahlt, können Sie anbieten, die Karte zu speichern. In diesem Fall wird ein Kartentoken generiert, das für zukünftige Zahlungen verwendet werden kann. Wenn es verwendet wird, muss der Kunde die Kartendaten nicht erneut manuell eingeben.';
$_['gopay_card_token_warning']        = 'Aktivieren Sie diese Option nur, wenn das Speichern von Karten / die Tokenisierung für Ihr GoPay-Händlerkonto aktiv ist. Wenn Ihr Konto diese Funktion nicht unterstützt, kann der Bezahlvorgang fehlschlagen und Zahlungen können nicht verfügbar sein.';

# Error
$_['error_permission']                = 'Sie benötigen eine Erlaubnis zum Ändern der Zahlung GoPay!';