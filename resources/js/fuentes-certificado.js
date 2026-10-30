import '@fontsource/playfair-display/latin-400.css';
import '@fontsource/playfair-display/latin-400-italic.css';
import '@fontsource/playfair-display/latin-700.css';
import '@fontsource/playfair-display/latin-700-italic.css';
import '@fontsource/lato/latin-400.css';
import '@fontsource/lato/latin-400-italic.css';
import '@fontsource/lato/latin-700.css';
import '@fontsource/lato/latin-700-italic.css';
import '@fontsource/cormorant-garamond/latin-400.css';
import '@fontsource/cormorant-garamond/latin-400-italic.css';
import '@fontsource/cormorant-garamond/latin-700.css';
import '@fontsource/cormorant-garamond/latin-700-italic.css';
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-400-italic.css';
import '@fontsource/inter/latin-700.css';
import '@fontsource/inter/latin-700-italic.css';
import '@fontsource/libre-baskerville/latin-400.css';
import '@fontsource/libre-baskerville/latin-400-italic.css';
import '@fontsource/libre-baskerville/latin-700.css';
import '@fontsource/libre-baskerville/latin-700-italic.css';
import '@fontsource/source-sans-3/latin-400.css';
import '@fontsource/source-sans-3/latin-400-italic.css';
import '@fontsource/source-sans-3/latin-700.css';
import '@fontsource/source-sans-3/latin-700-italic.css';
import '@fontsource/eb-garamond/latin-400.css';
import '@fontsource/eb-garamond/latin-400-italic.css';
import '@fontsource/eb-garamond/latin-700.css';
import '@fontsource/eb-garamond/latin-700-italic.css';
import '@fontsource/montserrat/latin-400.css';
import '@fontsource/montserrat/latin-400-italic.css';
import '@fontsource/montserrat/latin-700.css';
import '@fontsource/montserrat/latin-700-italic.css';
import '@fontsource/lora/latin-400.css';
import '@fontsource/lora/latin-400-italic.css';
import '@fontsource/lora/latin-700.css';
import '@fontsource/lora/latin-700-italic.css';

export const FUENTES_CERTIFICADO = [
    'Arial',
    'Arial Narrow',
    'Agency FB',
    'Bahnschrift SemiBold Condensed',
    'Calibri',
    'Cambria',
    'Times New Roman',
    'Lucida Bright',
    'Monotype Corsiva',
    'Georgia',
    'Verdana',
    'Tahoma',
    'Courier New',
    'Playfair Display',
    'Lato',
    'Cormorant Garamond',
    'Inter',
    'Libre Baskerville',
    'Source Sans 3',
    'EB Garamond',
    'Montserrat',
    'Lora',
];

// Las variantes se cargan antes de medir los textos o exportar el lienzo.
export async function cargarFuentesCertificado() {
    if (!document.fonts?.load) return;
    await Promise.allSettled(FUENTES_CERTIFICADO.flatMap(fuente =>
        ['normal 400', 'normal 700', 'italic 400', 'italic 700']
            .map(variante => document.fonts.load(`${variante} 16px "${fuente}"`)),
    ));
}
