import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch'; // Importation de la fonction apiFetch
import { useEffect, useState } from '@wordpress/element'; // Importation des hooks

registerBlockType('mon-plugin-meteo/mon-bloc', {
  edit: (props) => {
    const { attributes, setAttributes } = props;
    const blockProps = useBlockProps();
    const [status, setStatus] = useState("Chargement de la météo...");

    useEffect(() => {
      // Vérifiez si le navigateur supporte la géolocalisation
      if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
          // En cas de succès
          (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            setStatus(`Météo pour Lat: ${lat}, Lon: ${lon}...`);

            // Appel à l'API REST de WordPress pour récupérer les données météo
            // Vous devez d'abord créer cette route API en PHP
            apiFetch({
              path: `/mon-plugin-meteo/v1/weather?lat=${lat}&lon=${lon}`,
              method: 'GET'
            }).then( (weatherData) => {
                if (weatherData && !weatherData.error) {
                    setAttributes({
                        temperature: weatherData.temp,
                        condition: weatherData.condition,
                        location: weatherData.location
                    });
                } else {
                    setStatus(weatherData.error || "Aucune donnée météo disponible.");
                }
            });
          },
          // En cas d'erreur
          (error) => {
            if (error.code === error.PERMISSION_DENIED) {
              setStatus("Veuillez autoriser l'accès à votre localisation.");
            } else {
              setStatus("Erreur lors de la récupération de la localisation.");
            }
          }
        );
      } else {
        setStatus("La géolocalisation n'est pas supportée par ce navigateur.");
      }
    }, []);

    // Affichage dans l'éditeur de texte
    return (
      <div {...blockProps}>
        {attributes.temperature ? (
          <div>
            <h3>{attributes.location}</h3>
            <p>Température : {attributes.temperature}°C</p>
            <p>Conditions : {attributes.condition}</p>
          </div>
        ) : (
          <p>{status}</p>
        )}
      </div>
    );
  },
  save: () => {
    const blockProps = useBlockProps.save();
    return (
      <div {...blockProps}>
        {/* Le contenu du front-end sera généré par la fonction PHP */}
        <p>Chargement de la météo...</p>
      </div>
    );
  },
});