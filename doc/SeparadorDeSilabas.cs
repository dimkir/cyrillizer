using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Collections;

namespace ServicioSilabeo
{
	public class SeparadorDeSilabas
	{
		const int MAX_SILABAS = 20;

        const int VOC_FUERTE = 0;
        const int VOC_DEBIL = 2;
        const int VOC_DEBIL_ACENTUADA = 1;

		int lonPal;		      // Longitud de la palabra
		int numSil;           // Número de silabas de la palabra
		int tonica;           // Posición de la silaba tónica (empieza en 1)
		bool encTonica;       // Indica si se ha encontrado la silaba tónica
		int letraTildada;     // Posición de la letra tildda, si la hay 
		ArrayList posiciones; // Posiciones de inicio de las silabas
		String ultPal;        // Última palabra tratada, se guarda para
						      // no repetir el proceso si se pide la misma

		// En la mayoría de las lenguas, las palabras pueden dividirse en sílabas
		// que constan de un núcleo silábico, un ataque que antecede al núcleo
		// silábico y una coda que sigue al núcleo silábico
		// (http://es.wikipedia.org/wiki/Sílaba)


		/// <summary>
		/// Constructor
		/// </summary>
		public SeparadorDeSilabas()
		{
			ultPal = String.Empty; 
			posiciones = new ArrayList ();
		}

		/// <summary>
		/// Devuelve un array con las posiciones de inicio de las sílabas de palabra
		/// </summary>
		/// <param name="palabra"></param>
		/// <returns></returns>
		public ArrayList PosicionSilabas (String palabra)
		{
			Calcular (palabra);
			return posiciones;
		}

		/// <summary>
		/// Devuelve el número de silabas de palabra
		/// </summary>
		/// <param name="palabra"></param>
		/// <returns></returns>
		public int NumeroSilabas (String palabra)
		{
			Calcular (palabra);
			return numSil;
		}

		/// <summary>
		/// Devuelve la posición de la sílaba tónica de palabra
		/// </summary>
		/// <param name="palabra"></param>
		/// <returns></returns>
		public int SilabaTonica (String palabra)
		{
			Calcular (palabra);
			return tonica;
		}
		/// <summary>
		/// Determina si una palabra está correctamente tildada
		/// </summary>
		/// <param name="silabeo"></param>
		/// <param name="palabra"></param>
		/// <returns>
		/// 0 - bien tildada
		/// 7 - varias tildes en la palabra
		/// 8 - aguda mal tildada
		/// 9 - llana mal tildada
		/// </returns>
		public int BienTildada(ArrayList silabeo, string palabra)
		{
			int numSilabas = (int)silabeo[0];

			// Comprueba si hay má de una tilde en la palabra
			if (palabra.ToLower().Count<char>(TieneTilde) > 1) return 7;
			int posTónica =  (int)silabeo[numSilabas + 1];

			if (numSilabas - posTónica < 2) // Si la palabra no es esdrújula
			{
				char ultCar = palabra[palabra.Length -1];
				int final = (posTónica < numSilabas ? (int) silabeo[posTónica + 1]: palabra.Length) - (int) silabeo[posTónica] ;
				string silaba = palabra.Substring((int)silabeo[posTónica], final).ToLower();
				int i;

				// Se busca si hay tilde en la sílaba tónica
				for (i= 0; i < silaba.Length; i++)
				{
					if ("áéíóú".IndexOf(silaba[i]) > -1)
						break;
				}

				if (i < silaba.Length) // Hay tilde en la sílaba tónica
				{
					// La palabra es aguda y no termina en n, s, vocal -> error
					if ((posTónica == numSilabas) && ("nsáéíióúu".IndexOf(ultCar) == -1))
						return 8;

					// La palabra es llana y termina en n, s, vocal -> error
					if ((posTónica == numSilabas - 1) && ("nsaeiou".IndexOf(ultCar) != -1))
						return 9;
				}
			}

			return 0; // La palabra está correctamente tildada
		}

		/*********************************************************/
		/*********************************************************/
		/**             OPERACIONES PRIVADAS                    **/
		/*********************************************************/
		/*********************************************************/

		/// <summary>
		/// Determina si un caracter está tildado.
		/// </summary>
		/// <param name="c"></param>
		/// <returns></returns>
		private bool TieneTilde(char c) 
		{
			if ("áéíóú".IndexOf(c) != -1)
				return true;
			else
				return false;
		}

		/// <summary>
		/// Determina si hay que llamar a PosicionSilabas (si palabra
		/// es la misma que la última consultada, no hace falta)
		/// </summary>
		/// <param name="palabra"></param>
		public void  Calcular (String palabra)
		{
			if (palabra != ultPal) {
				ultPal = palabra.ToLower();
    			PosicionSilabas ();
			}
		}

		/// <summary>
		/// Determina si c es una vocal fuerte o débil acentuada
		/// </summary>
		/// <param name="c"></param>
		/// <returns></returns>
		bool VocalFuerte (char c) {
			switch (c) {
			case 'a': case 'á': case 'A': case 'Á': case 'à': case 'À':
			case 'e': case 'é': case 'E': case 'É': case 'è': case 'È':
			case 'í': case 'Í': case 'ì': case 'Ì':
			case 'o': case 'ó': case 'O': case 'Ó': case 'ò': case 'Ò':
			case 'ú': case 'Ú': case 'ù': case 'Ù':
				return true;
			}
			return false;
		}

		/// <summary>
		/// Determina si c no es una vocal
		/// </summary>
		/// <param name="c"></param>
		/// <returns></returns>
		bool esConsonante (char c) {

			if (!VocalFuerte(c))
			{
				switch (c)
				{
					// Vocal débil
					case 'i': case 'I': 
					case 'u': case 'U': case 'ü': case 'Ü':
						return false;
				}

				return true;
			}
			
			return false;
		}

		/// <summary>
		/// Determina si se forma un hiato
		/// </summary>
		/// <returns></returns>
		bool Hiato () {
			char tildado = ultPal [letraTildada];
	
			if ((letraTildada > 1) &&  // Sólo es posible que haya hiato si hay tilde
				(ultPal [letraTildada - 1] == 'u') && 
				(ultPal [letraTildada - 2] == 'q'))
				return false; // La 'u' de "qu" no forma hiato
	
			// El caracter central de un hiato debe ser una vocal cerrada con tilde
	
			if ((tildado == 'í') || (tildado == 'ì') || (tildado == 'ú') || (tildado == 'ù')) {

				if ((letraTildada > 0) && VocalFuerte (ultPal [letraTildada - 1])) return true;

				if ((letraTildada < (lonPal - 1)) && VocalFuerte (ultPal [letraTildada + 1])) return true;		
			}

			return false;
		}

		/// <summary>
		/// Determina el ataque de la silaba de pal que empieza
		/// en pos y avanza pos hasta la posición siguiente al
		/// final de dicho ataque
		/// </summary>
		/// <param name="pal"></param>
		/// <param name="pos"></param>
		int Ataque (String pal, int pos) {
			// Se considera que todas las consonantes iniciales forman parte del ataque
	
			char ultimaConsonante = 'a';
			while ((pos < lonPal) && ((esConsonante (pal [pos])) && (pal [pos] != 'y'))) {
				ultimaConsonante = pal [pos];
				pos++;
			}
	
			// (q | g) + u (ejemplo: queso, gueto)
	
			if (pos < lonPal - 1)
				if (pal [pos] == 'u') {
					if (ultimaConsonante == 'q') pos++;
					else
						if (ultimaConsonante == 'g') {
							char letra = pal [pos + 1];
							if ((letra == 'e') || (letra == 'é') || (letra == 'i') || (letra == 'í')) pos ++;
						}
				}
				else { // La u con diéresis se añade a la consonante
					if (((char) pal [pos] == 'ü') || ((char) pal [pos] == 'Ü'))
						if (ultimaConsonante == 'g') pos++;
				}

			return pos;
		}

		/// <summary>
		/// Determina el núcleo de la silaba de pal cuyo ataque
		/// termina en pos - 1 y avanza pos hasta la posición
		/// siguiente al final de dicho núcleo
		/// </summary>
		/// <param name="pal"></param>
		/// <param name="pos"></param>
		int Nucleo (String pal, int pos) {
			int anterior = 0;	// Sirve para saber el tipo de vocal anterior cuando hay dos seguidas
								// 0 = fuerte
								// 1 = débil acentuada
								// 2 = débil
			if (pos >= lonPal) return pos; // ¡¿No tiene núcleo?!

			// Se salta una 'y' al principio del núcleo, considerándola consonante
	
			if (pal [pos] == 'y') pos++;
	 
			// Primera vocal (por aquí queremos saber: si podemos hacer el diptongo (entongo) o no?
            //                  el entongo. No puede empezar con la debíl accentuada.
	
			if (pos < lonPal) {
				char c = pal [pos];
				switch (c) {
				// Vocal acentuada (fuerte o débil )
				case 'á': case 'Á': case 'à': case 'À':
				case 'é': case 'É': case 'è': case 'È':
				case 'ó':case 'Ó': case 'ò': case 'Ò':
					letraTildada = pos;
 					encTonica    = true;
					anterior = 0; // fuerte? 
					pos++;
					break;
				// Vocal fuerte
				case 'a': case 'A':
				case 'e': case 'E':
				case 'o': case 'O':
                        // no tonic
                        // no letraTildada
					anterior = 0; // fuerte
					pos++;
					break;

                // Vocal débil acentuada, rompe cualquier posible diptongo
				case 'í': case 'Í': case 'ì': case 'Ì':
				case 'ú': case 'Ú': case 'ù': case 'Ù': case 'ü': case 'Ü':
					letraTildada = pos;
                    encTonica = true;
                    anterior = 1; // debil accentuada
					pos++;
					return pos; 
				// Vocal débil
				case 'i': case 'I':
				case 'u': case 'U':
					anterior = 2; // debil 
					pos++;
					break;
				}
			}
	
			// 'h' intercalada en el núcleo, no condiciona diptongos o hiatos

			bool hache = false;
			if (pos < lonPal) {
				if (pal [pos] == 'h') {
					pos++;
					hache = true;
				}
			}
	
			// Segunda vocal 
            //
            // (aquí ya sabemos que hay vocal anterior). Se puede ser entongo 
            // (composicion de los vocales en la misma silaba, o puede ser un hiato - composicion de los vocales que pertenecen a silabas diferentes).
            // así vamos a intentar a saber si la anterior vocal y la proxima pueden formar un entongo 
	
			if (pos < lonPal) {
				char c = pal [pos];
				switch (c) {
				// Vocal acentuada (fuerte )
				case 'á': case 'Á': case 'à': case 'À':
				case 'é': case 'É': case 'è': case 'È':
				case 'ó':case 'Ó': case 'ò': case 'Ò':
					letraTildada = pos;
					if (anterior != VOC_FUERTE) {
						encTonica    = true;
					}

					if (anterior == VOC_FUERTE)
					{    // Dos vocales fuertes no forman silaba
						if (hache) pos--;
						return pos;
					}
					else
					{
						pos++;
					}

					break;
				// Vocal fuerte 
				case 'a': case 'A':
				case 'e': case 'E':
				case 'o': case 'O':	
					if (anterior == VOC_FUERTE) {    // Dos vocales fuertes no forman silaba
						if (hache) pos--;
						return pos;
					}
					else {
						pos++;
					}
				
					break;

				// Vocal débil acentuada, no puede haber triptongo, pero si diptongo
				case 'í': case 'Í': case 'ì': case 'Ì':
				case 'ú': case 'Ú': case 'ù': case 'Ù':
					letraTildada = pos;
			
					if (anterior != VOC_FUERTE) {  // Se forma diptongo
						encTonica    = true;
						pos++;
					}
					else
						if (hache) pos--;

					return pos;
				// Vocal débil
				case 'i': case 'I':
				case 'u': case 'U': case 'ü': case 'Ü':
					if (pos < lonPal - 1) { // ¿Hay tercera vocal? (si hay tercera vocal, eso significa que la segunda vocal es el principio de la proxima silaba.
						char siguiente = pal [pos + 1];
						if (!esConsonante (siguiente)) {
							char letraAnterior = pal [pos - 1];
							if (letraAnterior == 'h') pos--;
							return pos;
						}
					}

					// dos vocales débiles iguales no forman diptongo
                        // BUG: what about 'h' ? here we look at previous letter?
					if (pal [pos] != pal [pos - 1]) pos++;

					return pos;  // Es un diptongo plano o descendente	
				}//switch
			}
	
			// ¿tercera vocal?
	
			if (pos < lonPal) {
				char c = pal [pos];
				if ((c == 'i') || (c == 'u')) { // Vocal débil
					pos++;
					return pos;  // Es un triptongo	
				}
			}

			return pos;
		}

		/// <summary>
		/// Determina la coda de la silaba de pal cuyo núcleo
		/// termina en pos - 1 y avanza pos hasta la posición
		/// siguiente al final de dicha coda
		/// </summary>
		/// <param name="pal"></param>
		/// <param name="pos"></param>
		int Coda (String pal, int pos) {	
			if ((pos >= lonPal) || (!esConsonante (pal [pos])))
				return pos; // No hay coda
			else {
				if (pos == lonPal - 1) // Final de palabra
				{
					pos++;
					return pos;
				}

				// Si sólo hay una consonante entre vocales, pertenece a la siguiente silaba

				if (!esConsonante (pal [pos + 1])) return pos;

				char c1 = pal [pos];
				char c2 = pal [pos + 1];
		
				// ¿Existe posibilidad de una tercera consonante consecutina?
		
				if ((pos < lonPal - 2)) {
					char c3 = pal [pos + 2];
			
					if (!esConsonante (c3)) { // No hay tercera consonante
						// Los grupos ll, lh, ph, ch y rr comienzan silaba
				
						if ((c1 == 'l') && (c2 == 'l')) return pos;
						if ((c1 == 'c') && (c2 == 'h')) return pos;
						if ((c1 == 'r') && (c2 == 'r')) return pos;

						///////// grupos nh, sh, rh, hl son ajenos al español(DPD)
						if ((c1 != 's') && (c1 != 'r') &&
							(c2 == 'h'))
							return pos;

						// Si la y está precedida por s, l, r, n o c (consonantes alveolares),
						// una nueva silaba empieza en la consonante previa, si no, empieza en la y
				
						if ((c2 == 'y')) {
							if ((c1 == 's') || (c1 == 'l') || (c1 == 'r') || (c1 == 'n') || (c1 == 'c'))
								return pos;

							pos++;
							return pos;
						}

						// gkbvpft + l

						if ((((c1 == 'b')||(c1 == 'v')||(c1 == 'c')||(c1 == 'k')||
							   (c1 == 'f')||(c1 == 'g')||(c1 == 'p')||(c1 == 't')) && 
							  (c2 == 'l')
							 )
							) {
							return pos;
						}

						// gkdtbvpf + r

						if ((((c1 == 'b')||(c1 == 'v')||(c1 == 'c')||(c1 == 'd')||(c1 == 'k')||
			    			   (c1 == 'f')||(c1 == 'g')||(c1 == 'p')||(c1 == 't')) && 
							  (c2 == 'r')
							 )
						   ) {
							return pos;
						}

						pos++;
						return pos;
					}
					else { // Hay tercera consonante
						if ((pos + 3) == lonPal) { // Tres consonantes al final ¿palabras extranjeras?
							if ((c2 == 'y')) { // 'y' funciona como vocal
								if ((c1 == 's') || (c1 == 'l') || (c1 == 'r') || (c1 == 'n') || (c1 == 'c'))
									return pos;
							}
					
							if (c3 == 'y') { // 'y' final funciona como vocal con c2
								pos++;
							}
							else {	// Tres consonantes al final ¿palabras extranjeras?
								pos += 3;
							}
							return pos;
						}

						if ((c2 == 'y')) { // 'y' funciona como vocal
							if ((c1 == 's') || (c1 == 'l') || (c1 == 'r') || (c1 == 'n') || (c1 == 'c'))
								return pos;
						
							pos++;
							return pos;
						}

						// Los grupos pt, ct, cn, ps, mn, gn, ft, pn, cz, tz, ts comienzan silaba (Bezos)
				
						if ((c2 == 'p') && (c3 == 't') ||
							(c2 == 'c') && (c3 == 't') ||
							(c2 == 'c') && (c3 == 'n') ||
							(c2 == 'p') && (c3 == 's') ||
							(c2 == 'm') && (c3 == 'n') ||
							(c2 == 'g') && (c3 == 'n') ||
							(c2 == 'f') && (c3 == 't') ||
							(c2 == 'p') && (c3 == 'n') ||
							(c2 == 'c') && (c3 == 'z') ||
							(c2 == 't') && (c3 == 's') ||
							(c2 == 't') && (c3 == 's'))
						{
							pos++;
							return pos;
						}

						if ((c3 == 'l') || (c3 == 'r') ||    // Los grupos consonánticos formados por una consonante
															 // seguida de 'l' o 'r' no pueden separarse y siempre inician
															 // sílaba 
							((c2 == 'c') && (c3 == 'h')) ||  // 'ch'
							(c3 == 'y')) {                   // 'y' funciona como vocal
							pos++;  // Siguiente sílaba empieza en c2
						}
						else 
							pos += 2; // c3 inicia la siguiente sílaba
					}
				}
				else {
					if ((c2 == 'y')) return pos;

					pos +=2; // La palabra acaba con dos consonantes
				}
			}
			return pos;
		}

		/// <summary>
		/// Devuelve un array con las posiciones de inicio de las sílabas de ultPal
		/// </summary>
		void PosicionSilabas () {
			posiciones.Clear();

			lonPal       = ultPal.Length;
			encTonica    = false;
			tonica       = 0;
			numSil       = 0;
			letraTildada = -1;

			// Se recorre la palabra buscando las sílabas

			for (int actPos = 0; actPos < lonPal; )
			{
				numSil++;
				posiciones.Add(actPos);  // Marca el principio de la silaba actual

				// Las sílabas constan de tres partes: ataque, núcleo y coda

				actPos = Ataque(ultPal, actPos);
				actPos = Nucleo(ultPal, actPos);
				actPos = Coda(ultPal, actPos);
		
				if ((encTonica) && (tonica == 0)) tonica = numSil; // Marca la silaba tónica
			}
	
			// Si no se ha encontrado la sílaba tónica (no hay tilde), se determina en base a
			// las reglas de acentuación
	
			if (!encTonica) {
				if (numSil < 2) tonica = numSil;  // Monosílabos
				else {                            // Polisílabos
					char letraFinal    = ultPal [lonPal - 1];
					char letraAnterior = ultPal [lonPal - 2];
			
					if ((!esConsonante (letraFinal) || (letraFinal == 'y')) ||
						(((letraFinal == 'n') || (letraFinal == 's') && !esConsonante (letraAnterior))))
						tonica = numSil - 1;	// Palabra llana
					else
						tonica = numSil;		// Palabra aguda
				}
			}
		}
	}
}