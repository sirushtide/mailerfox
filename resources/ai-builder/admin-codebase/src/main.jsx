import React from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import App from './App.jsx';
import './styles.css';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }
    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }
    render() {
        if (this.state.hasError) {
            return React.createElement('div', { style: { padding: '24px', fontFamily: 'system-ui, sans-serif' } },
                React.createElement('h2', { style: { color: '#dc2626', fontSize: '16px', marginBottom: '8px' } }, 'Preview Error'),
                React.createElement('pre', { style: { background: '#fef2f2', border: '1px solid #fecaca', borderRadius: '8px', padding: '12px', fontSize: '12px', color: '#991b1b', whiteSpace: 'pre-wrap', wordBreak: 'break-word' } }, String(this.state.error?.message || this.state.error || 'Unknown error'))
            );
        }
        return this.props.children;
    }
}

createRoot(document.getElementById('root')).render(
    React.createElement(React.StrictMode, null,
        React.createElement(ErrorBoundary, null,
            React.createElement(HashRouter, null, React.createElement(App))
        )
    )
);
