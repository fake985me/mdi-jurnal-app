// Section templates based on existing public pages

export const sectionTemplates = [
  // Hero Sections
  {
    id: 'hero-gradient',
    name: "Banner Utama - Latar Gradasi",
    category: 'hero',
    thumbnail: '🎨',
    template: {
      section_type: 'hero',
      content: {
        heading: "Selamat Datang di Platform Kami",
        subheading: "Wujudkan ide Anda dengan solusi kami",
        button_text: "Mulai",
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
    name: "Banner Utama - Gambar Latar",
    category: 'hero',
    thumbnail: '🖼️',
    template: {
      section_type: 'hero',
      content: {
        heading: "Transformasikan Bisnis Anda",
        subheading: "Solusi inovatif untuk tantangan masa kini",
        button_text: "Pelajari Lebih Lanjut",
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
    name: "Fitur - Kisi 3 Kolom",
    category: 'features',
    thumbnail: '📊',
    template: {
      section_type: 'features',
      content: {
        heading: "Fitur Kami",
        subheading: "Semua yang Anda butuhkan untuk berkembang",
        features: [
          {
            icon: '⚡',
            title: "Kinerja Cepat",
            description: "Waktu muat cepat dan kinerja optimal",
          },
          {
            icon: '🔒',
            title: "Aman dan Andal",
            description: "Keamanan tingkat perusahaan untuk data Anda",
          },
          {
            icon: '🎨',
            title: "Desain Menarik",
            description: "Antarmuka pengguna modern dan responsif",
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
    name: "Fitur - Daftar dengan Ikon",
    category: 'features',
    thumbnail: '📋',
    template: {
      section_type: 'features',
      content: {
        heading: "Mengapa Memilih Kami",
        features: [
          { icon: '✓', title: "Mudah Digunakan", description: "Antarmuka yang mudah dipahami" },
          { icon: '✓', title: "Dapat Dikembangkan", description: "Berkembang bersama bisnis Anda" },
          { icon: '✓', title: "Dukungan 24/7", description: "Selalu siap membantu" },
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
    name: "Konten - Dua Kolom",
    category: 'content',
    thumbnail: '📄',
    template: {
      section_type: 'content',
      content: {
        heading: "Tentang Solusi Kami",
        text: "Kami menyediakan solusi teknologi terkini untuk membantu transformasi digital bisnis. Platform kami menawarkan perangkat dan fitur lengkap untuk menyederhanakan operasional Anda.",
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
    name: "Konten - Teks Rata Tengah",
    category: 'content',
    thumbnail: '📝',
    template: {
      section_type: 'content',
      content: {
        heading: "Misi Kami",
        text: "Mendukung bisnis dengan solusi teknologi inovatif yang mendorong pertumbuhan dan keberhasilan di era digital.",
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
    name: "Galeri - Tata Letak Kisi",
    category: 'gallery',
    thumbnail: '🖼️',
    template: {
      section_type: 'gallery',
      content: {
        heading: "Proyek Kami",
        subheading: "Karya terbaru yang kami banggakan",
        items: [
          {
            image_url: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f',
            title: "Proyek Alfa",
            description: "Solusi perusahaan",
          },
          {
            image_url: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab',
            title: "Proyek Beta",
            description: "Aplikasi seluler",
          },
          {
            image_url: 'https://images.unsplash.com/photo-1497366216548-37526070297c',
            title: "Proyek Gama",
            description: "Platform web",
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
    name: "Ajakan Bertindak - Gradasi",
    category: 'cta',
    thumbnail: '🎯',
    template: {
      section_type: 'cta',
      content: {
        heading: "Siap Memulai?",
        text: "Bergabunglah bersama ribuan pelanggan yang puas",
        button_text: "Mulai Uji Coba Gratis",
        button_url: '#',
        secondary_button_text: "Pelajari Lebih Lanjut",
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
    name: "Kontak - Formulir dan Informasi",
    category: 'contact',
    thumbnail: '📞',
    template: {
      section_type: 'contact',
      content: {
        heading: "Hubungi Kami",
        subheading: "Kami senang mendengar pendapat Anda",
        show_form: true,
        contact_info: [
          { type: 'email', value: 'info@example.com', icon: '📧' },
          { type: 'phone', value: '+1 234 567 890', icon: '📱' },
          { type: 'address', value: "Jl. Bisnis No. 123, Kota", icon: '📍' },
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
    name: "Statistik - 4 Kolom",
    category: 'stats',
    thumbnail: '📊',
    template: {
      section_type: 'stats',
      content: {
        stats: [
          { number: '10K+', label: "Pengguna Aktif" },
          { number: '50+', label: "Negara" },
          { number: '99.9%', label: "Waktu Aktif" },
          { number: '24/7', label: "Dukungan" },
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
    name: "Tim - Tata Letak Kisi",
    category: 'team',
    thumbnail: '👥',
    template: {
      section_type: 'team',
      content: {
        heading: "Kenali Tim Kami",
        subheading: "Sosok di balik keberhasilan kami",
        members: [
          {
            name: 'John Doe',
            role: "CEO dan Pendiri",
            image_url: 'https://ui-avatars.com/api/?name=John+Doe',
            bio: "Pemimpin visioner dengan pengalaman 15 tahun",
          },
          {
            name: 'Jane Smith',
            role: 'CTO',
            image_url: 'https://ui-avatars.com/api/?name=Jane+Smith',
            bio: "Ahli teknologi dan inovator",
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
    name: "Testimoni - Slide",
    category: 'testimonials',
    thumbnail: '💬',
    template: {
      section_type: 'testimonials',
      content: {
        heading: "Pendapat Klien Kami",
        testimonials: [
          {
            quote: "Platform ini mengubah operasional bisnis kami secara menyeluruh.",
            author: 'Sarah Johnson',
            role: 'CEO, TechCorp',
            avatar: 'https://ui-avatars.com/api/?name=Sarah+Johnson',
          },
          {
            quote: "Layanan istimewa dengan hasil luar biasa.",
            author: 'Michael Brown',
            role: "Direktur, InnovateCo",
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
    name: "Harga - 3 Tingkat",
    category: 'pricing',
    thumbnail: '💰',
    template: {
      section_type: 'pricing',
      content: {
        heading: "Pilih Paket Anda",
        subheading: "Harga fleksibel untuk setiap kebutuhan",
        plans: [
          {
            name: "Pemula",
            price: '$29',
            period: "bulan",
            features: ["Fitur 1", "Fitur 2", "Fitur 3"],
            button_text: "Mulai",
            highlighted: false,
          },
          {
            name: "Profesional",
            price: '$99',
            period: "bulan",
            features: ["Semua fitur paket Pemula", "Fitur 4", "Fitur 5", "Dukungan Prioritas"],
            button_text: "Mulai",
            highlighted: true,
          },
          {
            name: "Perusahaan",
            price: "Kustom",
            period: '',
            features: ["Semua fitur paket Profesional", "Integrasi khusus", "Dukungan khusus"],
            button_text: "Hubungi Kami",
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
    name: "FAQ - Akordeon",
    category: 'faq',
    thumbnail: '❓',
    template: {
      section_type: 'faq',
      content: {
        heading: "Pertanyaan yang Sering Diajukan",
        questions: [
          {
            question: "Bagaimana cara memulai?",
            answer: "Daftar akun dan ikuti panduan singkat kami.",
          },
          {
            question: "Metode pembayaran apa yang tersedia?",
            answer: "Kami menerima kartu kredit utama, PayPal, dan transfer bank.",
          },
          {
            question: "Bisakah saya membatalkan kapan saja?",
            answer: "Ya, Anda dapat membatalkan langganan kapan saja tanpa penalti.",
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
    name: "Pendaftaran Buletin",
    category: 'newsletter',
    thumbnail: '📧',
    template: {
      section_type: 'newsletter',
      content: {
        heading: "Dapatkan Kabar Terbaru",
        text: "Berlangganan buletin kami untuk kabar dan penawaran terbaru",
        button_text: "Berlangganan",
        placeholder: "Masukkan email Anda",
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
