// Section templates based on existing public pages

export const sectionTemplates = [
  // Hero Sections
  {
    id: 'hero-gradient',
    name: 'Hero - Gradient Background',
    category: 'hero',
    thumbnail: '🎨',
    template: {
      section_type: 'hero',
      content: {
        heading: 'Welcome to Our Platform',
        subheading: 'Build amazing things with our solutions',
        button_text: 'Get Started',
        button_url: '#',
        background_type: 'gradient',
        gradient: 'from-indigo-600 to-purple-600',
        text_color: 'white',
      },
      settings: {
        height: 'screen',
        alignment: 'center',
      },
      is_visible: true,
    },
  },
  {
    id: 'hero-image',
    name: 'Hero - With Background Image',
    category: 'hero',
    thumbnail: '🖼️',
    template: {
      section_type: 'hero',
      content: {
        heading: 'Transform Your Business apa yang ini',
        subheading: 'Innovative solutions for modern challenges',
        button_text: 'Learn More',
        button_url: '#',
        background_type: 'image',
        image_url: 'https://images.unsplash.com/photo-1497366216548-37526070297c',
        overlay: true,
        text_color: 'white',
      },
      settings: {
        height: 'screen',
        alignment: 'left',
      },
      is_visible: true,
    },
  },

  // Feature Sections
  {
    id: 'features-3col',
    name: 'Features - 3 Column Grid',
    category: 'features',
    thumbnail: '📊',
    template: {
      section_type: 'features',
      content: {
        heading: 'Our Features',
        subheading: 'Everything you need to succeed',
        features: [
          {
            icon: '⚡',
            title: 'Fast Performance',
            description: 'Lightning-fast load times and optimal performance',
          },
          {
            icon: '🔒',
            title: 'Secure & Reliable',
            description: 'Enterprise-grade security for your data',
          },
          {
            icon: '🎨',
            title: 'Beautiful Design',
            description: 'Modern and responsive user interface',
          },
        ],
      },
      settings: {
        columns: 3,
        style: 'cards',
      },
      is_visible: true,
    },
  },
  {
    id: 'features-list',
    name: 'Features - List with Icons',
    category: 'features',
    thumbnail: '📋',
    template: {
      section_type: 'features',
      content: {
        heading: 'Why Choose Us',
        features: [
          { icon: '✓', title: 'Easy to Use', description: 'Intuitive interface' },
          { icon: '✓', title: 'Scalable', description: 'Grows with your business' },
          { icon: '✓', title: '24/7 Support', description: 'Always here to help' },
        ],
      },
      settings: {
        layout: 'list',
        iconStyle: 'circle',
      },
      is_visible: true,
    },
  },

  // Content Sections
  {
    id: 'content-2col',
    name: 'Content - Two Column',
    category: 'content',
    thumbnail: '📄',
    template: {
      section_type: 'content',
      content: {
        heading: 'About Our Solutions',
        text: 'We provide cutting-edge solutions that help businesses transform digitally. Our platform offers comprehensive tools and features designed to streamline your operations.',
        image_url: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f',
        image_position: 'right',
      },
      settings: {
        layout: 'two-column',
        imageWidth: '50%',
      },
      is_visible: true,
    },
  },
  {
    id: 'content-centered',
    name: 'Content - Centered Text',
    category: 'content',
    thumbnail: '📝',
    template: {
      section_type: 'content',
      content: {
        heading: 'Our Mission',
        text: 'To empower businesses with innovative technology solutions that drive growth and success in the digital age.',
      },
      settings: {
        layout: 'centered',
        maxWidth: '800px',
      },
      is_visible: true,
    },
  },

  // Gallery/Projects
  {
    id: 'gallery-grid',
    name: 'Gallery - Grid Layout',
    category: 'gallery',
    thumbnail: '🖼️',
    template: {
      section_type: 'gallery',
      content: {
        heading: 'Our Projects',
        subheading: 'Recent work we\'re proud of',
        items: [
          {
            image_url: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f',
            title: 'Project Alpha',
            description: 'Enterprise solution',
          },
          {
            image_url: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab',
            title: 'Project Beta',
            description: 'Mobile application',
          },
          {
            image_url: 'https://images.unsplash.com/photo-1497366216548-37526070297c',
            title: 'Project Gamma',
            description: 'Web platform',
          },
        ],
      },
      settings: {
        columns: 3,
        style: 'grid',
        gap: 'large',
      },
      is_visible: true,
    },
  },

  // CTA Sections
  {
    id: 'cta-gradient',
    name: 'Call to Action - Gradient',
    category: 'cta',
    thumbnail: '🎯',
    template: {
      section_type: 'cta',
      content: {
        heading: 'Ready to Get Started?',
        text: 'Join thousands of satisfied customers today',
        button_text: 'Start Free Trial',
        button_url: '#',
        secondary_button_text: 'Learn More',
        secondary_button_url: '#',
      },
      settings: {
        background: 'gradient',
        gradient: 'from-blue-600 to-indigo-600',
        textColor: 'white',
      },
      is_visible: true,
    },
  },

  // Contact Section
  {
    id: 'contact-form',
    name: 'Contact - Form & Info',
    category: 'contact',
    thumbnail: '📞',
    template: {
      section_type: 'contact',
      content: {
        heading: 'Get in Touch',
        subheading: 'We\'d love to hear from you',
        show_form: true,
        contact_info: [
          { type: 'email', value: 'info@example.com', icon: '📧' },
          { type: 'phone', value: '+1 234 567 890', icon: '📱' },
          { type: 'address', value: '123 Business St, City', icon: '📍' },
        ],
      },
      settings: {
        layout: 'split',
        formPosition: 'left',
      },
      is_visible: true,
    },
  },

  // Stats Section
  {
    id: 'stats-4col',
    name: 'Statistics - 4 Column',
    category: 'stats',
    thumbnail: '📊',
    template: {
      section_type: 'stats',
      content: {
        stats: [
          { number: '10K+', label: 'Active Users' },
          { number: '50+', label: 'Countries' },
          { number: '99.9%', label: 'Uptime' },
          { number: '24/7', label: 'Support' },
        ],
      },
      settings: {
        columns: 4,
        style: 'minimal',
        background: 'gray',
      },
      is_visible: true,
    },
  },

  // Team Section
  {
    id: 'team-grid',
    name: 'Team - Grid Layout',
    category: 'team',
    thumbnail: '👥',
    template: {
      section_type: 'team',
      content: {
        heading: 'Meet Our Team',
        subheading: 'The people behind our success',
        members: [
          {
            name: 'John Doe',
            role: 'CEO & Founder',
            image_url: 'https://ui-avatars.com/api/?name=John+Doe',
            bio: 'Visionary leader with 15 years experience',
          },
          {
            name: 'Jane Smith',
            role: 'CTO',
            image_url: 'https://ui-avatars.com/api/?name=Jane+Smith',
            bio: 'Technology expert and innovator',
          },
        ],
      },
      settings: {
        columns: 3,
        showBio: true,
      },
      is_visible: true,
    },
  },

  // Testimonials
  {
    id: 'testimonials-slider',
    name: 'Testimonials - Slider',
    category: 'testimonials',
    thumbnail: '💬',
    template: {
      section_type: 'testimonials',
      content: {
        heading: 'What Our Clients Say',
        testimonials: [
          {
            quote: 'This platform transformed our business operations completely.',
            author: 'Sarah Johnson',
            role: 'CEO, TechCorp',
            avatar: 'https://ui-avatars.com/api/?name=Sarah+Johnson',
          },
          {
            quote: 'Outstanding service and incredible results.',
            author: 'Michael Brown',
            role: 'Director, InnovateCo',
            avatar: 'https://ui-avatars.com/api/?name=Michael+Brown',
          },
        ],
      },
      settings: {
        layout: 'cards',
        autoplay: true,
      },
      is_visible: true,
    },
  },

  // Pricing
  {
    id: 'pricing-3tier',
    name: 'Pricing - 3 Tier',
    category: 'pricing',
    thumbnail: '💰',
    template: {
      section_type: 'pricing',
      content: {
        heading: 'Choose Your Plan',
        subheading: 'Flexible pricing for every need',
        plans: [
          {
            name: 'Starter',
            price: '$29',
            period: 'month',
            features: ['Feature 1', 'Feature 2', 'Feature 3'],
            button_text: 'Get Started',
            highlighted: false,
          },
          {
            name: 'Professional',
            price: '$99',
            period: 'month',
            features: ['All Starter features', 'Feature 4', 'Feature 5', 'Priority Support'],
            button_text: 'Get Started',
            highlighted: true,
          },
          {
            name: 'Enterprise',
            price: 'Custom',
            period: '',
            features: ['All Pro features', 'Custom integration', 'Dedicated support'],
            button_text: 'Contact Us',
            highlighted: false,
          },
        ],
      },
      settings: {
        columns: 3,
      },
      is_visible: true,
    },
  },

  // FAQ
  {
    id: 'faq-accordion',
    name: 'FAQ - Accordion',
    category: 'faq',
    thumbnail: '❓',
    template: {
      section_type: 'faq',
      content: {
        heading: 'Frequently Asked Questions',
        questions: [
          {
            question: 'How do I get started?',
            answer: 'Simply sign up for an account and follow our quick start guide.',
          },
          {
            question: 'What payment methods do you accept?',
            answer: 'We accept all major credit cards, PayPal, and bank transfers.',
          },
          {
            question: 'Can I cancel anytime?',
            answer: 'Yes, you can cancel your subscription at any time without penalty.',
          },
        ],
      },
      settings: {
        layout: 'accordion',
        columns: 1,
      },
      is_visible: true,
    },
  },

  // Newsletter
  {
    id: 'newsletter-simple',
    name: 'Newsletter Signup',
    category: 'newsletter',
    thumbnail: '📧',
    template: {
      section_type: 'newsletter',
      content: {
        heading: 'Stay Updated',
        text: 'Subscribe to our newsletter for the latest updates and offers',
        button_text: 'Subscribe',
        placeholder: 'Enter your email',
      },
      settings: {
        layout: 'inline',
        background: 'light',
      },
      is_visible: true,
    },
  },
];

export const getTemplatesByCategory = () => {
  const categories = {};
  sectionTemplates.forEach(template => {
    if (!categories[template.category]) {
      categories[template.category] = [];
    }
    categories[template.category].push(template);
  });
  return categories;
};

export const getCategoryNames = () => {
  return [...new Set(sectionTemplates.map(t => t.category))];
};
