export function money(value) {
  return Number(value || 0).toLocaleString('fr-MA', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

export function dh(value, devise = 'MAD') {
  const unit = !devise || devise === 'MAD' ? 'DH' : devise;
  return `${money(value)} ${unit}`;
}

export function dateFr(value) {
  if (!value) return '—';
  const [y, m, d] = String(value).slice(0, 10).split('-');
  return y && m && d ? `${d}/${m}/${y}` : String(value);
}

export function apiError(error) {
  const data = error?.response?.data;
  if (data?.errors) {
    return Object.values(data.errors).flat().join(' ');
  }
  return data?.message || 'Opération impossible.';
}

export function yearStart() {
  return `${new Date().getFullYear()}-01-01`;
}

export function today() {
  return new Date().toISOString().slice(0, 10);
}

export async function downloadFile(url, params, filename) {
  const response = await window.axios.get(url, { params, responseType: 'blob' });
  const type = response.headers['content-type'] || '';
  const blob = new Blob([response.data], { type });
  if (params.format === 'print' || params.format === 'pdf') {
    const href = URL.createObjectURL(blob);
    window.open(href, '_blank');
    return;
  }
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
  URL.revokeObjectURL(link.href);
}
