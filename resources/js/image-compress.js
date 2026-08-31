window.compressImage = async function (file, { maxDim = 1280, quality = 0.7 } = {}) {
  if (!file.type.startsWith('image/')) return file;

  let img;
  try {
    img = await createImageBitmap(file);
  } catch (e) {
    return file; // non-decodable image -> leave original, server handles
  }

  const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.max(1, Math.round(img.width * scale));
  canvas.height = Math.max(1, Math.round(img.height * scale));
  canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);

  try {
    const type = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
    const blob = await new Promise((resolve, reject) =>
      canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('image encoding failed'))), type, quality)
    );

    const name = type === 'image/jpeg'
      ? file.name.replace(/\.[^.]+$/i, '.jpg')
      : file.name;
    return new File([blob], name, {
      type,
      lastModified: Date.now(),
    });
  } catch (e) {
    return file;
  }
};
