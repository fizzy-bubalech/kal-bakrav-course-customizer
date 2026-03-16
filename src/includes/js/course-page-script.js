
// Ensure DOM is fully loaded before starting
function ensureDOMReady() {
    return new Promise(resolve => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', resolve);
        } else {
            resolve();
        }
    });
}

function is_topic_completed(){
if (document.querySelector('.learndash_mark_complete_button')) return false;

return true;
}

function disable_next_button() {
    if (is_topic_completed()) return;

    // Find all ld-button links
    const links = document.querySelectorAll('a.ld-button');
    
    // Find the specific "Next Lesson" button by checking inner text
    const nextLessonLink = Array.from(links).find(link => {
        const span = link.querySelector('span');
        return span && span.textContent.includes('Next Lesson');
    });

    if (nextLessonLink) {
        nextLessonLink.style.pointerEvents = 'none';
        nextLessonLink.style.opacity = '0.5';
        nextLessonLink.style.cursor = 'not-allowed';
        nextLessonLink.style.filter = "blur(0.5rem)";
        
        // Prevent the default link behavior
        nextLessonLink.addEventListener('click', (e) => {
            e.preventDefault();
        });
    }
}
 
async function startScript() {
    try {
        // Ensure DOM is ready before starting
        await ensureDOMReady();
        // Initialize the application
        await disable_next_button();
        
        console.log('Script started successfully');
    } catch (error) {
        console.error('Failed to start script', error);
    }
}

// Start the application when the script loads
startScript();
