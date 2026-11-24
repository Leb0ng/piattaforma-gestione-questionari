package it.leonsciurti.questionari.modello;

import java.time.LocalDate;
import java.time.Period;

// La fascia d'eta NON e' salvata nel db: si calcola ogni volta dalla data di nascita,
// cosi resta giusta anche con il passare degli anni.
// Stessi confini usati dal Back Office (funzioni.php -> fasciaEta):
//   UNDER_30 -> meno di 30 anni, DA_30_A_50 -> da 30 a 50, OVER_50 -> piu di 50
public enum FasciaEta {
	UNDER_30, DA_30_A_50, OVER_50;

	public static FasciaEta di(LocalDate dataNascita) {
		if (dataNascita == null) {
			return null;
		}
		int anni = Period.between(dataNascita, LocalDate.now()).getYears();
		if (anni < 30) {
			return UNDER_30;
		}
		return anni <= 50 ? DA_30_A_50 : OVER_50;
	}
}
