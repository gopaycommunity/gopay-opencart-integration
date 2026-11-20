<?php
# Heading
$_['heading_title']                   = 'OpenCart GoPay gateway';

# Text
$_['text_extension']                  = 'Extensions';
$_['text_edit']                       = 'GoPay payment gateway';
$_['text_success']                    = 'GoPay modul plaćanja je uspješno modificiran!';

# GoPay plugin config entry
$_['gopay_enable_disable']            = 'Omogući onemogući';
$_['gopay_inline']                    = 'Inline pristupnik za plaćanje';
$_['gopay_title']                     = 'Titula';
$_['gopay_description']               = 'Opis';
$_['gopay_goid']                      = 'GoID';
$_['gopay_client_id']                 = 'Client ID';
$_['gopay_client_secret']             = 'Client Secret';
$_['gopay_test']                      = 'Testni način rada';
$_['gopay_default_language']          = 'Zadani jezik';
$_['gopay_shipping_methods']          = 'Omogućite načine dostave';
$_['gopay_countries']                 = 'Omogući zemlje';
$_['gopay_simplified_bank']           = 'Odabir banke';
$_['gopay_payment_methods']           = 'Omogućite GoPay načine plaćanja';
$_['gopay_banks']                     = 'Omogućite banke';
$_['gopay_payment_retry']             = 'Ponovi metodu plaćanja';
$_['gopay_card_token']                = 'Plaćanje spremljenom karticom';

# GoPay plugin config placeholder
$_['gopay_title_placeholder']         = 'Umetni naslov plaćanja...';
$_['gopay_description_placeholder']   = 'Umetni opis...';
$_['gopay_goid_placeholder']          = 'Umetnite svoj GoID...';
$_['gopay_client_id_placeholder']     = 'Unesite svoj GoPay ID klijenta...';
$_['gopay_client_secret_placeholder'] = 'Umetnite svoj GoPay Client Secret Token...';

# GoPay plugin config tooltip
$_['gopay_enable_disable_tooltip']    = 'Omogućite pristupnik za plaćanje GoPay.';
$_['gopay_inline_tooltip']            = 'Inline payment gateway pokreće se neposredno iznad prodajnog mjesta.';
$_['gopay_title_tooltip']             = 'Naziv načina plaćanja koji se prikazuje na blagajni.';
$_['gopay_description_tooltip']       = 'Opis načina plaćanja koji je prikazan na blagajni.';
$_['gopay_goid_tooltip']              = 'Unesite svoj jedinstveni GoID koji se može pronaći u postavkama vašeg GoPay računa.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Više informacija</a>.';
$_['gopay_client_id_tooltip']         = 'Unesite svoj jedinstveni ID klijenta koji se može pronaći u postavkama vašeg GoPay računa.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Više informacija</a>.';
$_['gopay_client_secret_tooltip']     = 'Unesite svoj Client Secret Token, koji se može pronaći u postavkama vašeg GoPay računa.<a href="https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway"> Više informacija</a>.';
$_['gopay_test_tooltip']              = 'Omogućite testni način rada GoPay pristupnika plaćanja.';
$_['gopay_default_language_tooltip']  = 'Zadani jezik koristi se kada je korisnik web-mjesta e-trgovine iz zemlje čiji jezik nije podržan.';
$_['gopay_shipping_methods_tooltip']  = 'Omogućite GoPay pristupnik plaćanja samo za odabrane OpenCart načine dostave.';
$_['gopay_countries_tooltip']         = 'Omogućite pristupnik za plaćanje GoPay samo za odabrane zemlje.';
$_['gopay_simplified_bank_tooltip']   = 'Ako je omogućeno, klijenti ne mogu birati nijednu određenu banku na blagajni, oni su grupirani u jednu opciju "Bankovni račun", ali moraju odabrati banku nakon što se pozove GoPay pristupnik za plaćanje.';
$_['gopay_payment_methods_tooltip']   = 'Omogućite samo odabrane načine plaćanja na GoPay pristupniku za plaćanje.';
$_['gopay_banks_tooltip']             = 'Omogućite samo odabrane banke na GoPay pristupniku za plaćanje.';
$_['gopay_payment_retry_tooltip']     = 'Ako je omogućeno, ponovni pokušaj plaćanja neuspjelog plaćanja izvršit će se istim načinom plaćanja koji je odabran kada je kupac slao narudžbu.';
$_['gopay_card_token_tooltip']        = 'Kada korisnik plaća karticom, možete mu ponuditi spremanje kartice. U tom slučaju generirat će se token kartice koji se može koristiti za buduća plaćanja. Ako se koristi, korisnik neće morati ponovno ručno unositi podatke o kartici.';
$_['gopay_card_token_warning']        = 'Omogućite ovu opciju samo ako je spremanje kartice / tokenizacija aktivna za vaš GoPay trgovački račun. Ako vaš račun ne podržava ovu funkciju, proces naplate može ne uspjeti, a plaćanja mogu postati nedostupna.';

# Error
$_['error_permission']                = 'Trebate dopuštenje za izmjenu plaćanja GoPay!';
$_['error_credentials']               = 'Neispravan Client ID ili Client Secret. GoPay pristupnik plaćanja je onemogućen.';
$_['error_goid']                      = 'Neispravan GoID. GoPay pristupnik plaćanja je onemogućen.';
$_['error_methods_unavailable']       = 'Načine plaćanja nije bilo moguće učitati iz GoPaya. Zadržani su posljednji učitani načini plaćanja. Ponovno učitajte stranicu kasnije.';