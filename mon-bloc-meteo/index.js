import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType('mon-plugin-meteo/mon-bloc', {
  edit: (props) => {
    const blockProps = useBlockProps();
    return (
      <div {...blockProps}>
        <p>Bloc Météo - En cours d'édition...</p>
        {/* Ici, vous afficherez la météo dans l'éditeur de texte */}
      </div>
    );
  },
  save: (props) => {
    const blockProps = useBlockProps.save();
    return (
      <div {...blockProps}>
        {/* La fonction "save" génère le HTML final qui sera affiché sur le site. [cite: 8] */}
        <p>Chargement de la météo...</p>
        {/* Le contenu sera chargé dynamiquement par votre code PHP */}
      </div>
    );
  },
});