export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <circle cx="24" cy="10" r="5" fill="currentColor" />
            <circle cx="10" cy="26" r="5" fill="currentColor" opacity="0.85" />
            <circle cx="38" cy="26" r="5" fill="currentColor" opacity="0.85" />
            <circle cx="16" cy="40" r="4.5" fill="currentColor" opacity="0.7" />
            <circle cx="32" cy="40" r="4.5" fill="currentColor" opacity="0.7" />
            <path
                d="M24 15V20M24 20L10 21M24 20L38 21M10 31V34.5M38 31V34.5M16 26.5C16 26.5 20 32 16 35.5M32 26.5C32 26.5 28 32 32 35.5"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
            />
        </svg>
    );
}
