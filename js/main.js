let files = [];

function copyLink() {
	const link = document.getElementById("link").href;
	navigator.clipboard.writeText(link).then(() => {
		const copied = document.getElementById("copied");
		copied.style.display = "inline";
		setTimeout(() => copied.style.display = "none", 2000);
	});
}

function changeLang(lang) {
	const url = new URL(window.location);
	url.searchParams.set('lang', lang);
	window.location = url.toString();
}

const form = document.getElementById('uploadForm');

form.addEventListener('submit', async (e) => {
	e.preventDefault();

	const uploadId = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('');
	const modeField = form.querySelector('[name="mode"]');
	const pwField = form.querySelector('[name="pw"]');
	const mode = modeField ? modeField.value : 'once';
	const pw = pwField ? pwField.value : '';
	const paths = JSON.parse(document.getElementById('paths').value || '[]');
	const progressBar = document.getElementById('progressBar');
	progressBar.style.display = 'block';
	const progressText = document.getElementById('progressText');
	progressText.style.display = 'block';

	let totalUploaded = 0;
	const totalBytes = files.reduce((sum, file) => sum + file.size, 0);

	for (let i = 0; i < files.length; i++) {
		const file = files[i];
		const chunkSize = 1024 * 1024 * 10; // 10 MB
		const totalChunks = Math.ceil(file.size / chunkSize);
		const rawName = paths[i] || file.name;
		const name = rawName.replace(/^(\.\.[\/\\])+/, '').replace(/^\/+/, '');

		for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
			const chunk = file.slice(chunkIndex * chunkSize, (chunkIndex + 1) * chunkSize);
			const formData = new FormData();
			formData.append('chunk', chunk);
			formData.append('chunkIndex', chunkIndex);
			formData.append('totalChunks', totalChunks);
			formData.append('uploadId', uploadId);
			formData.append('name', name);
			formData.append('pw', pw);
			formData.append('mode', mode);
			const isLastFile = (i === files.length - 1 && chunkIndex === totalChunks - 1) ? '1' : '0';
			formData.append('isLastFile', isLastFile);

			try {
				const response = await fetch('', {
					method: 'POST',
					body: formData
				});

				totalUploaded += chunk.size;
				const percent = Math.min(100, Math.round((totalUploaded / totalBytes) * 100));
				progressBar.value = percent;
				progressText.textContent = t.upload_text + ' ' + percent + '%';
				if (percent >= 90) {
					progressText.style.display = 'none';
					document.getElementById('uploadStatusText').textContent = t.creating_zip;
					document.getElementById('uploadStatusText').style.display = 'block';
				}

				if (isLastFile === '1') {
					if (response.ok) {
						document.getElementById('uploadStatusText').style.display = 'none';
						progressText.textContent = (t.upload_success || 'finished');
						progressText.style.display = 'block';
						progressText.style.color = '#4caf50';
						const html = await response.text();
						document.querySelector('p').innerHTML = html;
					} else {
						alert(t.upload_error);
					}
				}
			} catch (err) {
				alert(t.upload_error);
				progressBar.style.display = 'none';
				progressText.style.display = 'none';
				document.getElementById('uploadStatusText').style.display = 'none';
				return;
			}
		}
	}
});


// Dropzone handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const selectedFileDiv = document.getElementById('selectedFile');

function updateSelectedFile() {
	if (fileInput.files.length > 0) {
		let names = [];
		for (let i = 0; i < fileInput.files.length; i++) {
			names.push(fileInput.files[i].name);
		}
		selectedFileDiv.textContent = (names.length > 1 ? t.selected_files_plural : t.selected_files) + ": " + names.join(', ');
	} else {
		selectedFileDiv.textContent = "";
	}
}

async function traverseFileTree(item, path = '', fileList = []) {
	if (item.isFile) {
		await new Promise(resolve => {
			item.file(file => {
				const fileWithPath = new File([file], path + file.name, { type: file.type });
				fileList.push(fileWithPath);
				resolve();
			});
		});
	} else if (item.isDirectory) {
		const reader = item.createReader();
		await new Promise(resolve => {
			reader.readEntries(async entries => {
				for (const entry of entries) {
					await traverseFileTree(entry, path + item.name + '/', fileList);
				}
				resolve();
			});
		});
	}
}

dropzone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
	files = Array.from(fileInput.files);
	document.getElementById('paths').value = JSON.stringify(files.map(f => f.name));
	updateSelectedFile();
});

dropzone.addEventListener('dragover', e => {
	e.preventDefault();
	dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
	dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', async (e) => {
	e.preventDefault();
	dropzone.classList.remove('dragover');

	const dtItems = e.dataTransfer.items;
	const dtFiles = e.dataTransfer.files;

	files = [];

	if (dtItems && dtItems.length > 0) {
		const entriesProcessed = [];

		for (let i = 0; i < dtItems.length; i++) {
			const item = dtItems[i].webkitGetAsEntry?.();
			if (item) {
				entriesProcessed.push(traverseFileTree(item, '', files));
			}
		}

		await Promise.all(entriesProcessed);
	}

	if (files.length === 0 && dtFiles.length > 0) {
		files = Array.from(dtFiles);
	}

	const dataTransfer = new DataTransfer();
	for (const file of files) {
		dataTransfer.items.add(file);
	}
	fileInput.files = dataTransfer.files;

	document.getElementById('paths').value = JSON.stringify(files.map(f => f.name));
	updateSelectedFile();
});
