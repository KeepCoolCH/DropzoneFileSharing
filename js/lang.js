function changeLang(lang) {
    const url = new URL(window.location);
    url.searchParams.set('lang', lang);
    window.location = url.toString();
}
