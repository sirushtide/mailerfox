import { registerElement } from '../elementRegistry.js';
import * as CONTROL from '../controls/types.js';
import { Carousel } from '../reusable/carousel/Carousel.jsx';
import { carouselControls, carouselStyleControls } from '../reusable/carousel/schema.js';
import { carouselConfigFromProps, CAROUSEL_PROP_DEFAULTS } from '../reusable/carousel/config.js';
import { renderTestimonialCard, testimonialStyleSchema, testimonialStyleDefaults } from './testimonial.jsx';

export function renderTestimonialsPreview(widget, helpers = {}) {
    const p = widget.props || {};
    const globals = helpers.globals || {};
    const items = Array.isArray(p.items) ? p.items : [];

    return (
        <Carousel
            items={items}
            config={carouselConfigFromProps(p)}
            styleProps={p}
            renderItem={(item) => renderTestimonialCard(p, item, globals, widget.id || '')}
            className="mp-testimonials"
        />
    );
}

const testimonialsSchema = {
    content: [
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'Testimonials', bind: 'props.items', itemSchema: {
            itemLabel: 'Testimonial',
            accordion: true,
            defaultItem: () => ({ avatarUrl: '', avatarSvg: '', avatarSource: 'url', rating: 5, quote: 'Amazing product and support!', name: 'Jane Doe', role: 'CEO, Acme' }),
            fields: [
                { name: 'avatar', label: 'Avatar Image', type: 'media', urlKey: 'avatarUrl', svgKey: 'avatarSvg', sourceKey: 'avatarSource' },
                { name: 'rating', label: 'Rating (0-5)' },
                { name: 'quote', label: 'Quote', type: 'wysiwyg' },
                { name: 'name', label: 'Name' },
                { name: 'role', label: 'Role' },
            ],
        } },
        carouselControls(),
    ],
    style: [...testimonialStyleSchema, ...carouselStyleControls()],
    advanced: [],
};

registerElement({
    type: 'testimonials',
    label: 'Testimonial Carousel',
    group: 'Basic',
    defaultProps: {
        items: [
            { avatarUrl: '', avatarSvg: '', avatarSource: 'url', rating: 5, quote: 'Amazing product and support!', name: 'Jane Doe', role: 'CEO, Acme' },
            { avatarUrl: '', avatarSvg: '', avatarSource: 'url', rating: 5, quote: 'It saved our team hours every week.', name: 'John Smith', role: 'Founder, Beta' },
            { avatarUrl: '', avatarSvg: '', avatarSource: 'url', rating: 4, quote: 'The best tool we have adopted this year.', name: 'Ava Brown', role: 'CMO, Gamma' },
        ],
        ...CAROUSEL_PROP_DEFAULTS,
        carouselAutoplay: true,
        ...testimonialStyleDefaults,
    },
    render: renderTestimonialsPreview,
    schema: testimonialsSchema,
});
