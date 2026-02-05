// Section Components - Auto-imported by component registry
import HeroSection from './HeroSection.vue'
import FeaturesSection from './FeaturesSection.vue'
import GallerySection from './GallerySection.vue'
import CTASection from './CTASection.vue'
import TestimonialsSection from './TestimonialsSection.vue'
import ContactSection from './ContactSection.vue'
import ContentSection from './ContentSection.vue'
import CarouselSection from './CarouselSection.vue'

// Component mapping for dynamic rendering
export const SECTION_COMPONENTS = {
  hero: HeroSection,
  carousel: CarouselSection,
  features: FeaturesSection,
  gallery: GallerySection,
  cta: CTASection,
  testimonials: TestimonialsSection,
  contact: ContactSection,
  content: ContentSection,
}

// Section type definitions for builder UI
export const SECTION_TYPES = [
  {
    type: 'hero',
    name: 'Hero Banner',
    icon: '🎯',
    description: 'Full-width hero section with background image and CTA',
    defaultContent: {
      title: 'Welcome to Our Site',
      subtitle: 'Discover amazing products and services',
      ctaText: 'Get Started',
      ctaLink: '/product',
      backgroundImage: '',
    },
    defaultSettings: {
      height: 'full',
      textAlign: 'center',
      overlay: true,
      overlayOpacity: 0.5,
    },
  },
  {
    type: 'carousel',
    name: 'Carousel Hero',
    icon: '🎠',
    description: 'Hero section with multiple slides, auto-play, and navigation',
    defaultContent: {
      slides: [
        {
          title: 'Welcome to Our Platform',
          subtitle: 'Build amazing things with our solutions',
          ctaText: 'Get Started',
          ctaLink: '/product',
          gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        },
        {
          title: 'Innovative Solutions',
          subtitle: 'For modern challenges',
          ctaText: 'Learn More',
          ctaLink: '/solutions',
          gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        },
      ],
    },
    defaultSettings: {
      height: 'full',
      autoplay: true,
      interval: 7500,
      overlay: true,
      overlayOpacity: 0.3,
    },
  },
  {
    type: 'features',
    name: 'Features Grid',
    icon: '⚡',
    description: 'Showcase features in a responsive grid',
    defaultContent: {
      title: 'Our Features',
      subtitle: 'What makes us different',
      features: [
        { icon: 'check', title: 'Feature 1', description: 'Description here' },
        { icon: 'star', title: 'Feature 2', description: 'Description here' },
        { icon: 'heart', title: 'Feature 3', description: 'Description here' },
      ],
    },
    defaultSettings: {
      columns: 3,
      layout: 'grid',
      backgroundColor: '#F9FAFB',
    },
  },
  {
    type: 'gallery',
    name: 'Image Gallery',
    icon: '🖼️',
    description: 'Responsive image gallery with lightbox',
    defaultContent: {
      title: 'Gallery',
      subtitle: 'Our work',
      images: [],
    },
    defaultSettings: {
      columns: 3,
      spacing: 'normal',
      backgroundColor: '#FFFFFF',
    },
  },
  {
    type: 'cta',
    name: 'Call to Action',
    icon: '📢',
    description: 'Eye-catching CTA section',
    defaultContent: {
      title: 'Ready to Get Started?',
      description: 'Contact us today',
      buttonText: 'Contact Us',
      buttonLink: '/contact',
    },
    defaultSettings: {
      backgroundColor: '#4F46E5',
      textColor: '#FFFFFF',
    },
  },
  {
    type: 'testimonials',
    name: 'Testimonials',
    icon: '💬',
    description: 'Customer testimonials and reviews',
    defaultContent: {
      title: 'What Our Clients Say',
      subtitle: '',
      testimonials: [],
    },
    defaultSettings: {},
  },
  {
    type: 'contact',
    name: 'Contact Info',
    icon: '📞',
    description: 'Contact information and form',
    defaultContent: {
      title: 'Get In Touch',
      email: 'info@example.com',
      phone: '+62 123 456 7890',
      address: 'Jakarta, Indonesia',
    },
    defaultSettings: {},
  },
  {
    type: 'content',
    name: 'Rich Content',
    icon: '📝',
    description: 'Text content with optional image',
    defaultContent: {
      title: 'About Us',
      body: '<p>Your content here...</p>',
      image: '',
    },
    defaultSettings: {
      layout: 'text-only',
    },
  },
]

export {
  HeroSection,
  CarouselSection,
  FeaturesSection,
  GallerySection,
  CTASection,
  TestimonialsSection,
  ContactSection,
  ContentSection,
}
