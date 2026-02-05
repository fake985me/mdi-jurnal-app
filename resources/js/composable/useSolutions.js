import { ref } from 'vue'

// Images from public folder
const a = '/assets/static/solutions/training.jpg'
const b = '/assets/static/solutions/Remote.jpg'
const d = '/assets/static/solutions/managed.png'
const e = '/assets/static/solutions/cable.jpg'

export default function useSolutions() {
  const solutions = ref([
    {
      id: 1,
      name: 'Training',
      details: {
        imageUrl: a,
        title: 'Training',
        description:
          'This training is designed to provide a comprehensive understanding of GPON (Gigabit Passive Optical Network) technology, covering basic theory, network architecture, device introduction, to installation and configuration practices. GPON is a modern fiber optic network solution that is widely used by internet service providers (ISPs), telecommunications operators, and enterprises.',
      },
    },
    {
      id: 2,
      name: 'Maintenance and Support Services',
      details: {
        imageUrl: b,
        title: 'Remote and On-Site Support',
        description:
          'With proper maintenance and taking preventive measures to avoid future network issues, such as regular maintenance and security audits, we can ensure smooth operations, minimize downtime, increase productivity, and protect data from the risk of loss or security breaches.',
      },
    },
    {
      id: 3,
      name: 'Managed Services',
      details: {
        imageUrl: d,
        title: 'Eficient network management',
        description:
          'We provide services and set clear SLAs, as appropriate and effective solutions, optimizing the use of IT resources, to improve the operational efficiency of your business.',
      },
    },
    {
      id: 4,
      name: 'Design and Build',
      details: {
        imageUrl: e,
        title: 'true and correct installation of equipment',
        description:
          'Our team of experts will help handle the process from design, consultation and installation, according to what you want.',
      },
    },
  ])

  const loading = ref(false)
  const error = ref(null)

  return {
    solutions,
    loading,
    error
  }
}
