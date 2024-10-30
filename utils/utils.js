/**
 * @param {string} str
 */
export const parsePostalCode = (str) => {
  const parsed = str.replace(/\s/g, "").toUpperCase();
  return /^\d{4}[A-Z]{2}$/.test(parsed) ? parsed : null;
};
