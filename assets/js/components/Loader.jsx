import { __ } from '@wordpress/i18n';

const Loader = ({ message = __('Loading...', 'gemini-command-center') }) => {
    return (
        <div className="gcc-loader">
            <div className="gcc-spinner"></div>
            <p>{message}</p>
        </div>
    );
};

export default Loader;