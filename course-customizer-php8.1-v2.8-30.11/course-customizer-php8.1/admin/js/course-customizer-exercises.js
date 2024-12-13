
  const formatResult = (result, isTime) => {
    if (!isTime) {
      return escapeHtml(result);
    }

    // Convert seconds to HH:MM:SS format
    const hours = Math.floor(result / 3600);
    const minutes = Math.floor((result % 3600) / 60);
    const seconds = result % 60;

    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
  };

