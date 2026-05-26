// Administrative Personnel Data
const adminData = {
    'managing-director': {
        image: '../assets/managing director/managing director1.jpg',
        title: 'Managing Director',
        position: 'Managing Director',
        message: 'Welcome to Rose Valley Academy. As Managing Director, I am committed to fostering an environment where academic excellence meets character development. Our vision is to transform education by empowering students with knowledge, critical thinking skills, and moral values that will serve them throughout their lives. We believe every student has the potential to achieve greatness, and our institution provides the platform and support to unlock that potential. Together, we build a community of learners who are not just academically proficient but also responsible global citizens.'
    },
    'dean': {
        image: '../assets/dean/dean1.jpg',
        title: 'Dean',
        position: 'Dean',
        message: 'As Dean of Rose Valley Academy, I oversee our academic programs and faculty development. Education is a transformative journey that extends beyond textbooks. Our dedicated faculty works tirelessly to create an engaging learning environment where students develop critical thinking skills and a passion for discovery. We encourage intellectual curiosity and provide opportunities for students to explore their interests through various academic and co-curricular programs. Our commitment is to nurture well-rounded individuals prepared for success in higher education and beyond.'
    },
    'deputy-dean': {
        image: '../assets/deputy dean/deputy deans.jpg',
        title: 'Deputy Dean',
        position: 'Deputy Dean',
        message: 'The Deputy Dean\'s office is dedicated to ensuring academic integrity and supporting student welfare. We believe in creating a supportive environment where every student feels valued and encouraged to achieve their best. Our role is to bridge the gap between administration and students, ensuring that all concerns are addressed promptly and fairly. We work collaboratively with parents, teachers, and students to create a holistic educational experience. Rose Valley Academy is committed to student success in all dimensions—academic, personal, and social.'
    },
    'admin-coordinator': {
        image: '../assets/administrative coordinator/administrative coordinator.jpg',
        title: 'Administrative Coordinator',
        position: 'Administrative Coordinator',
        message: 'The Administrative Coordinator\'s office ensures smooth operations of the institution, managing all administrative functions that support our educational mission. From admissions to records management, we work behind the scenes to create an efficient, organized institution. We pride ourselves on excellent service to students, parents, and staff. Our goal is to remove administrative barriers so that students can focus on their education and personal growth. We are here to assist with any administrative queries and ensure all processes are transparent and student-friendly.'
    }
};

// Get modal elements
const adminModal = document.getElementById('adminModal');
const adminModalClose = document.querySelector('.admin-modal-close');
const aboutCards = document.querySelectorAll('.about-card');

// Open modal when card is clicked
aboutCards.forEach(card => {
    card.addEventListener('click', () => {
        const personKey = card.getAttribute('data-person');
        const person = adminData[personKey];

        if (person) {
            // Populate modal
            document.getElementById('adminModalImage').src = person.image;
            document.getElementById('adminModalTitle').textContent = person.title;
            document.getElementById('adminModalPosition').textContent = person.position;
            document.getElementById('adminModalMessage').textContent = person.message;

            // Show modal
            adminModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });
});

// Close modal
adminModalClose.addEventListener('click', closeModal);

// Close modal when clicking outside
adminModal.addEventListener('click', (e) => {
    if (e.target === adminModal) {
        closeModal();
    }
});

function closeModal() {
    adminModal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && adminModal.classList.contains('active')) {
        closeModal();
    }
});
