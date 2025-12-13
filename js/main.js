let files = [];

function copyLink() {
	const link = document.getElementById("link").href;
	navigator.clipboard.writeText(link).then(() => {
		const copied = document.getElementById("copied");
		copied.style.display = "inline";
		setTimeout(() => copied.style.display = "none", 2000);
	});
}

const form = document.getElementById('uploadForm');

const UPLOAD_ENDPOINT = 'upload.php';
const CHUNK_SIZE = 1024 * 1024 * 5; // 5 MB
const MAX_RETRIES = 5;
const RETRY_BASE_DELAY = 1000; // ms

async function statusRequest(fileName, totalSize, extra = {}) {
	const fd = new FormData();
	fd.append("action", "status");
	fd.append("relativePath", fileName);
	fd.append("totalSize", totalSize);

	for (const [k,v] of Object.entries(extra)) {
		if (v !== undefined && v !== null) fd.append(k, v);
	}

	const r = await fetch(UPLOAD_ENDPOINT, { method: "POST", body: fd });
	const t = await r.text();
	if (t.startsWith("STATUS ")) return parseInt(t.split(" ")[1], 10) || 0;
	if (t.startsWith("OK "))     return 0;
	throw new Error("ERROR " + t);
}

function appendChunk(slice, relPath, totalSize, extra = {}, attempt = 0) {
	return new Promise((resolve, reject) => {
		const fd = new FormData();
		fd.append("action", "append");
		fd.append("chunk", slice);
		fd.append("totalSize", totalSize);

		for (const [k,v] of Object.entries(extra)) {
			if (v !== undefined && v !== null) fd.append(k, v);
		}

		const xhr = new XMLHttpRequest();
		xhr.open("POST", UPLOAD_ENDPOINT);
		xhr.onload = () => {
			const resp = xhr.responseText || "";
			if (xhr.status !== 200) {
				if (attempt + 1 <= MAX_RETRIES) {
					const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
					return setTimeout(() => {
						appendChunk(slice, relPath, totalSize, extra, attempt + 1).then(resolve, reject);
					}, delay);
				}
				return reject(new Error(`Servererror ${xhr.status}: ${resp}`));
			}
			if (resp.startsWith("ERR")) {
				if (attempt + 1 <= MAX_RETRIES) {
					const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
					return setTimeout(() => {
						appendChunk(slice, relPath, totalSize, extra, attempt + 1).then(resolve, reject);
					}, delay);
				}
				return reject(new Error(resp));
			}
			resolve(resp);
		};
		xhr.onerror = () => {
			if (attempt + 1 <= MAX_RETRIES) {
				const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
				return setTimeout(() => {
					appendChunk(slice, relPath, totalSize, extra, attempt + 1).then(resolve, reject);
				}, delay);
			}
			reject(new Error("Networkerror"));
		};
		xhr.send(fd);
	});
}

async function finalizeRequest(fileName, totalSize, extra = {}) {
	const fd = new FormData();
	fd.append("action", "finalize");
	fd.append("relativePath", fileName);
	fd.append("totalSize", totalSize);

	for (const [k,v] of Object.entries(extra)) {
		if (v !== undefined && v !== null) fd.append(k, v);
	}

	const r = await fetch(UPLOAD_ENDPOINT, { method: "POST", body: fd });
	return r.text();
}

async function uploadFileResumable(file, relPath, startAt, updateBytesCb, extraFields) {
  let offset = startAt;
  const total = file.size;

  if (total === 0) {
    return finalizeRequest(relPath, 0, extraFields).catch(() => "");
  }

  while (offset < total) {
    const slice = file.slice(offset, offset + CHUNK_SIZE);
    const resp = await appendChunk(slice, relPath, total, extraFields);
    if (resp.includes("COMPLETE")) {
      updateBytesCb(total - startAt);
      return resp;
    }
    if (resp.startsWith("OK ")) {
      const rec = parseInt(resp.split(" ")[1], 10) || (offset + slice.size);
      const delta = rec - offset;
      offset = rec;
      updateBytesCb(delta);
      continue;
    }
    offset += slice.size;
    updateBytesCb(slice.size);
  }

  return finalizeRequest(relPath, total, extraFields).catch(() => "");
}

let currentUploadId = null;

if (!window._cleanupRegistered) {
  window.addEventListener("beforeunload", () => {
    if (currentUploadId) {
      const data = JSON.stringify({
        token: "serverintern",
        uploadId: currentUploadId,
        cleanup: true
      });

      navigator.sendBeacon(
        "delete_emailadress.php",
        new Blob([data], { type: "application/json" })
      );
    }
  });
  window._cleanupRegistered = true;
}

form.addEventListener('submit', async (e) => {
	e.preventDefault();
	
	const uploadId = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('');
		
	currentUploadId = uploadId;
	
	let linkShown = false;

	const mailChoiceEl = document.getElementById('mailChoice');
	const fixedMailChoice = mailChoiceEl ? mailChoiceEl.value : 'no';
	if (mailChoiceEl) mailChoiceEl.disabled = true;

	const uploaderEmail = form.querySelector('[name="uploader_email"]').value;
	const recipientEmail = form.querySelector('[name="recipient_email"]').value;
	
	await fetch('save_emailadress.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({
			uploadId,
			uploader_email: uploaderEmail,
			recipient_email: recipientEmail,
			token: "serverintern"
		})
	});
	
	const mode = form.querySelector('[name="mode"]').value;
	const pw = form.querySelector('[name="pw"]').value;
	const paths = JSON.parse(document.getElementById('paths').value || '[]');
	const progressBar = document.getElementById('progressBar');
	progressBar.style.display = 'block';
	const progressText = document.getElementById('progressText');
	progressText.style.display = 'block';

	let totalUploaded = 0;
	const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
	const currentLang = document.documentElement.lang || 'de';

	const commonExtra = {
		uploadId: uploadId,
		pw: pw,
		mode: mode,
		mailChoice: fixedMailChoice,
		totalFiles: files.length,
		lang: currentLang 
	};

	let lastHtml = "";
	
	for (let i = 0; i < files.length; i++) {
		const file = files[i];
		const rawName = paths[i] || file.name;

		const name = rawName.replace(/^(\.\.[\/\\])+/, '').replace(/^\/+/, '');
		const relPath = name;

		try {
			const startAt = await statusRequest(relPath, file.size, { ...commonExtra, relativePath: rawName });

			const htmlOrText = await uploadFileResumable(
				file,
				relPath,
				startAt,
				(deltaBytes) => {
					totalUploaded += deltaBytes;
					if (totalUploaded > totalBytes) totalUploaded = totalBytes;
					const percent = Math.min(100, Math.round((totalUploaded / totalBytes) * 100));
					progressBar.value = percent;
					const uploadedText = formatBytes(totalUploaded);
					const totalText = formatBytes(totalBytes);
					progressText.textContent = `${t.upload_text} ${uploadedText} / ${totalText} (${percent}%)`;
					if (percent >= 100) {
						progressText.style.display = 'none';
						document.getElementById('uploadStatusText').textContent = t.creating_zip;
						document.getElementById('uploadStatusText').style.display = 'block';
					}
				},
				{
					...commonExtra,
					relativePath: rawName
				}
			);
			
			if (typeof htmlOrText === "string") {
				lastHtml = htmlOrText;
			}

		} catch (err) {
			await fetch('delete_emailadress.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
							token: "serverintern",
							uploadId
					})
			});
			
			currentUploadId = null;
			
			alert(t.upload_error);
			progressBar.style.display = 'none';
			progressText.style.display = 'none';
			document.getElementById('uploadStatusText').style.display = 'none';
			return;
		}
	}

	if (lastHtml) {
		document.getElementById('uploadStatusText').style.display = 'none';
		progressText.textContent = (t.upload_success || 'finished');
		progressText.style.display = 'block';
		document.getElementById('uploadResult').innerHTML = lastHtml;
		
		currentUploadId = null;
	}
});


// Dropzone handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const selectedFileDiv = document.getElementById('selectedFile');

function buildTreeWithSizes(files) {
    const tree = {};
    let totalSize = 0;

    files.forEach(file => {
        const parts = (file.webkitRelativePath || file.name).split('/');
        let current = tree;
        parts.forEach((part, i) => {
            if (i === parts.length - 1) {
                current[part] = { size: file.size };
                totalSize += file.size;
            } else {
                if (!current[part]) {
                    current[part] = {};
                }
                current = current[part];
            }
        });
    });
    return { tree, totalSize };
}

function renderTree(tree) {
    let html = '<ul>';
    const keys = Object.keys(tree).sort((a, b) => a.localeCompare(b, 'de', { numeric: true }));

    const folders = keys.filter(k => tree[k] && tree[k].size === undefined);
    const files   = keys.filter(k => tree[k] && tree[k].size !== undefined);

    for (const key of folders) {
        html += `<li>📁 ${key}${renderTree(tree[key])}</li>`;
    }

    for (const key of files) {
        const size = formatBytes(tree[key].size);
        html += `<li>📄 ${key} (${size})</li>`;
    }

    html += '</ul>';
    return html;
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0.00 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(dm) + ' ' + sizes[i];
}

async function checkFreeSpace(totalSize) {
    try {
        const res = await fetch("check_disk_space.php");
        const data = await res.json();
        const free = data.free || 0;

        if (totalSize > free) {
            alert(`${t.diskspace_error}\n${t.diskspace_required} ${formatBytes(totalSize)}\n${t.diskspace_free} ${formatBytes(free)}`);
            return false;
        } else {
            return true;
        }
    } catch (e) {
        alert(`${t.diskspace_check_failed}`);
        return true;
    }
}

async function updateSelectedFile() {
    if (fileInput.files.length > 0) {
        const fileArr = Array.from(fileInput.files);
        const { tree, totalSize } = buildTreeWithSizes(fileArr);
        const totalSizeText = `<div style="margin-top:10px;">📦 ${formatBytes(totalSize)}</div>`;

        selectedFileDiv.innerHTML =
            (fileArr.length > 1 ? t.selected_files_plural : t.selected_files) +
            ":" + renderTree(tree) + totalSizeText;
        selectedFileDiv.classList.add("shown");
        
        const ok = await checkFreeSpace(totalSize);
        if (!ok) {
            fileInput.value = "";
            files = [];
            selectedFileDiv.innerHTML = "";
            selectedFileDiv.classList.remove("shown");
        }
    } else {
        selectedFileDiv.innerHTML = "";
        selectedFileDiv.classList.remove("shown");
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
