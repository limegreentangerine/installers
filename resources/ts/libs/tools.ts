export const debounce = <T extends (...args: any[]) => void>(fn: T, delay = 300) => {
	let timer: ReturnType<typeof setTimeout> | undefined;

	return (...args: Parameters<T>) => {
		clearTimeout(timer);
		timer = setTimeout(() => fn(...args), delay);
	};
};

export const formatDate = (value: Date | string): string => {
	const date = value instanceof Date ? value : new Date(value);
	const day = date.getDate();
	const ordinal =
		day % 100 >= 11 && day % 100 <= 13
			? 'th'
			: ({ 1: 'st', 2: 'nd', 3: 'rd' }[day % 10] ?? 'th');
	const month = date.toLocaleString('en', { month: 'long' });
	const hour = String(date.getHours()).padStart(2, '0');
	const minute = String(date.getMinutes()).padStart(2, '0');

	return `${day}${ordinal} ${month} ${date.getFullYear()} - ${hour}:${minute}`;
};

export const urlTo = (
	path: string,
	action: string = '',
	slugs: string[] | string | null = null
): string => {
	let url = path;

	if (action.length > 0) {
		url = `${url}/${action}`;
	}

	if (slugs !== null) {
		if (slugs instanceof Array) {
			for (const slug of slugs) {
				url = `${url}/${slug}`;
			}
		} else {
			url = `${url}/${slugs}`;
		}
	}

	return url;
};

export const navigateTo = (url: string): void => {
	window.location.href = url;
};
