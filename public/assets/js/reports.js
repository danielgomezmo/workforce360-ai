(() => {
    const reportRoot = document.querySelector('[data-report-export]');
    if (!reportRoot) return;

    const excelButton = document.querySelector('[data-report-excel]');
    const pdfButton = document.querySelector('[data-report-pdf]');
    const table = reportRoot.querySelector('[data-export-table]');

    const cleanText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();

    const meta = Array.from(reportRoot.querySelectorAll('[data-export-meta]')).map((item) => ({
        label: cleanText(item.dataset.label),
        value: cleanText(item.dataset.value)
    }));

    const summaries = Array.from(reportRoot.querySelectorAll('.report-summary-card')).map((card) => ({
        label: cleanText(card.querySelector('span')?.textContent),
        value: cleanText(card.querySelector('strong')?.textContent),
        detail: cleanText(card.querySelector('small')?.textContent)
    }));

    const reportTitle = cleanText(reportRoot.dataset.reportTitle || 'Reporte');
    const fileName = cleanText(reportRoot.dataset.fileName || 'Reporte_Workforce360_AI').replace(/[^a-zA-Z0-9_\-]/g, '_');
    const logoUrl = reportRoot.dataset.logoUrl || '';

    function tableData() {
        if (!table) return { headers: [], rows: [] };

        const headers = Array.from(table.querySelectorAll('thead th')).map((cell) => cleanText(cell.textContent));
        const rows = Array.from(table.querySelectorAll('tbody tr')).map((row) => {
            const cells = Array.from(row.querySelectorAll('td')).map((cell) => cleanText(cell.textContent));
            if (cells.length === 1 && row.querySelector('td[colspan]')) return null;
            return cells;
        }).filter(Boolean);

        return { headers, rows };
    }

    async function imageToDataUrl(url) {
        if (!url) return null;
        try {
            const response = await fetch(url, { cache: 'no-store' });
            if (!response.ok) return null;
            const blob = await response.blob();
            return await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        } catch (_) {
            return null;
        }
    }

    function setBusy(button, busy, text) {
        if (!button) return;
        if (busy) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${text}`;
        } else {
            button.disabled = false;
            if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
        }
    }

    function downloadBlob(blob, name) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function excelColumnName(number) {
        let name = '';
        let n = number;
        while (n > 0) {
            const rem = (n - 1) % 26;
            name = String.fromCharCode(65 + rem) + name;
            n = Math.floor((n - 1) / 26);
        }
        return name;
    }

    async function exportExcel() {
        if (!window.ExcelJS) {
            alert('No se pudo cargar el componente de Excel. Verifica tu conexión a Internet y vuelve a intentar.');
            return;
        }

        const { headers, rows } = tableData();
        if (!headers.length) {
            alert('No se encontró la tabla del reporte.');
            return;
        }

        setBusy(excelButton, true, 'Generando...');

        try {
            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Workforce360 AI';
            workbook.company = 'DEVIOZ';
            workbook.created = new Date();

            const sheetName = reportTitle.substring(0, 31) || 'Reporte';
            const worksheet = workbook.addWorksheet(sheetName, {
                views: [{ state: 'frozen', ySplit: 1 }],
                pageSetup: {
                    orientation: 'landscape',
                    paperSize: 9,
                    fitToPage: true,
                    fitToWidth: 1,
                    fitToHeight: 0,
                    margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 }
                }
            });

            const columnCount = Math.max(headers.length, 6);
            const lastCol = excelColumnName(columnCount);

            const logoData = await imageToDataUrl(logoUrl);
            if (logoData) {
                const imageId = workbook.addImage({ base64: logoData, extension: 'png' });
                const logoColumn = Math.max(0, (columnCount / 2) - 1.15);
                worksheet.addImage(imageId, { tl: { col: logoColumn, row: 0.15 }, ext: { width: 125, height: 48 } });
            }

            worksheet.getRow(1).height = 30;
            worksheet.getRow(2).height = 26;

            worksheet.mergeCells(`A3:${lastCol}3`);
            worksheet.getCell('A3').value = 'WORKFORCE360 AI';
            worksheet.getCell('A3').font = { bold: true, size: 18, color: { argb: 'FF053034' } };
            worksheet.getCell('A3').alignment = { horizontal: 'center', vertical: 'middle' };

            worksheet.mergeCells(`A4:${lastCol}4`);
            worksheet.getCell('A4').value = reportTitle.toUpperCase();
            worksheet.getCell('A4').font = { bold: true, size: 12, color: { argb: 'FF475569' } };
            worksheet.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };
            worksheet.getRow(3).height = 25;
            worksheet.getRow(4).height = 20;

            let rowIndex = 6;
            meta.forEach((item) => {
                worksheet.getCell(`A${rowIndex}`).value = item.label + ':';
                worksheet.getCell(`A${rowIndex}`).font = { bold: true, color: { argb: 'FF053034' } };
                worksheet.mergeCells(`B${rowIndex}:${lastCol}${rowIndex}`);
                worksheet.getCell(`B${rowIndex}`).value = item.value;
                worksheet.getCell(`B${rowIndex}`).alignment = { horizontal: 'left' };
                rowIndex += 1;
            });

            rowIndex += 1;
            const summaryStart = rowIndex;
            summaries.forEach((item, index) => {
                const col = index + 1;
                const cell = worksheet.getCell(summaryStart, col);
                cell.value = item.label;
                cell.font = { bold: true, size: 9, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF053034' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };

                const valueCell = worksheet.getCell(summaryStart + 1, col);
                valueCell.value = item.value;
                valueCell.font = { bold: true, size: 13, color: { argb: 'FF053034' } };
                valueCell.alignment = { horizontal: 'center', vertical: 'middle' };
                valueCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF2F6F5' } };

                const detailCell = worksheet.getCell(summaryStart + 2, col);
                detailCell.value = item.detail;
                detailCell.font = { size: 8, color: { argb: 'FF64748B' } };
                detailCell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                detailCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
            });
            worksheet.getRow(summaryStart).height = 24;
            worksheet.getRow(summaryStart + 1).height = 26;
            worksheet.getRow(summaryStart + 2).height = 26;

            rowIndex = summaryStart + 4;
            const headerRowIndex = rowIndex;
            const headerRow = worksheet.getRow(headerRowIndex);
            headers.forEach((header, index) => {
                const cell = headerRow.getCell(index + 1);
                cell.value = header;
                cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 9 };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF053034' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FF24484B' } },
                    bottom: { style: 'thin', color: { argb: 'FF24484B' } },
                    left: { style: 'thin', color: { argb: 'FF24484B' } },
                    right: { style: 'thin', color: { argb: 'FF24484B' } }
                };
            });
            headerRow.height = 30;

            rows.forEach((dataRow, rowOffset) => {
                const row = worksheet.getRow(headerRowIndex + rowOffset + 1);
                dataRow.forEach((value, index) => {
                    const cell = row.getCell(index + 1);
                    cell.value = value;
                    cell.alignment = { vertical: 'middle', wrapText: true };
                    cell.border = {
                        top: { style: 'hair', color: { argb: 'FFD8DEE5' } },
                        bottom: { style: 'hair', color: { argb: 'FFD8DEE5' } },
                        left: { style: 'hair', color: { argb: 'FFD8DEE5' } },
                        right: { style: 'hair', color: { argb: 'FFD8DEE5' } }
                    };
                    if (rowOffset % 2 === 1) {
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                    }
                });
            });

            headers.forEach((header, index) => {
                let maxLength = Math.max(10, cleanText(header).length + 2);
                rows.slice(0, 300).forEach((dataRow) => {
                    maxLength = Math.max(maxLength, cleanText(dataRow[index] || '').length + 2);
                });
                worksheet.getColumn(index + 1).width = Math.min(maxLength, 34);
            });

            worksheet.autoFilter = {
                from: { row: headerRowIndex, column: 1 },
                to: { row: headerRowIndex + Math.max(rows.length, 1), column: headers.length }
            };
            worksheet.views = [{ state: 'frozen', ySplit: headerRowIndex, xSplit: 0 }];
            worksheet.headerFooter.oddFooter = '&CWorkforce360 AI · DEVIOZ&RPage &P of &N';

            const buffer = await workbook.xlsx.writeBuffer();
            downloadBlob(
                new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
                `${fileName}.xlsx`
            );
        } catch (error) {
            console.error(error);
            alert('No se pudo generar el archivo Excel. Revisa la consola del navegador para más detalle.');
        } finally {
            setBusy(excelButton, false);
        }
    }

    async function exportPdf() {
        const jsPDFConstructor = window.jspdf?.jsPDF;
        if (!jsPDFConstructor) {
            alert('No se pudo cargar el componente de PDF. Verifica tu conexión a Internet y vuelve a intentar.');
            return;
        }

        const { headers, rows } = tableData();
        if (!headers.length) {
            alert('No se encontró la tabla del reporte.');
            return;
        }

        setBusy(pdfButton, true, 'Generando...');

        try {
            const doc = new jsPDFConstructor({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const logoData = await imageToDataUrl(logoUrl);

            if (logoData) {
                doc.addImage(logoData, 'PNG', (pageWidth - 34) / 2, 8, 34, 14);
            }

            doc.setTextColor(5, 48, 52);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text('WORKFORCE360 AI', pageWidth / 2, 28, { align: 'center' });

            doc.setTextColor(71, 85, 105);
            doc.setFontSize(11);
            doc.text(reportTitle.toUpperCase(), pageWidth / 2, 34, { align: 'center' });

            doc.setDrawColor(5, 48, 52);
            doc.setLineWidth(0.8);
            doc.line(12, 38, pageWidth - 12, 38);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(55, 65, 81);

            let metaY = 44;
            meta.forEach((item, index) => {
                const isRight = index % 2 === 1;
                const x = isRight ? pageWidth / 2 + 4 : 14;
                if (index > 0 && index % 2 === 0) metaY += 6;
                doc.setFont('helvetica', 'bold');
                doc.text(`${item.label}:`, x, metaY);
                doc.setFont('helvetica', 'normal');
                doc.text(item.value || '—', x + 24, metaY, { maxWidth: pageWidth / 2 - 42 });
            });

            let summaryY = metaY + 9;
            const summaryCount = Math.max(summaries.length, 1);
            const gap = 3;
            const usableWidth = pageWidth - 28;
            const boxWidth = Math.min(52, (usableWidth - gap * (summaryCount - 1)) / summaryCount);
            const totalWidth = boxWidth * summaryCount + gap * (summaryCount - 1);
            let boxX = (pageWidth - totalWidth) / 2;

            summaries.forEach((item) => {
                doc.setFillColor(242, 246, 245);
                doc.setDrawColor(203, 213, 225);
                doc.roundedRect(boxX, summaryY, boxWidth, 20, 2, 2, 'FD');
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(100, 116, 139);
                doc.setFontSize(6.5);
                doc.text(item.label.toUpperCase(), boxX + boxWidth / 2, summaryY + 5, { align: 'center', maxWidth: boxWidth - 4 });
                doc.setTextColor(5, 48, 52);
                doc.setFontSize(11);
                doc.text(item.value || '0', boxX + boxWidth / 2, summaryY + 11.5, { align: 'center', maxWidth: boxWidth - 4 });
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(100, 116, 139);
                doc.setFontSize(5.8);
                doc.text(item.detail || '', boxX + boxWidth / 2, summaryY + 16.5, { align: 'center', maxWidth: boxWidth - 4 });
                boxX += boxWidth + gap;
            });

            if (typeof doc.autoTable !== 'function') {
                throw new Error('jspdf-autotable no está disponible.');
            }

            doc.autoTable({
                head: [headers],
                body: rows,
                startY: summaryY + 25,
                margin: { left: 10, right: 10, bottom: 14 },
                styles: {
                    font: 'helvetica',
                    fontSize: 6.6,
                    cellPadding: 2,
                    valign: 'middle',
                    textColor: [31, 41, 55],
                    lineColor: [216, 222, 229],
                    lineWidth: 0.1,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: [5, 48, 52],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                alternateRowStyles: { fillColor: [248, 250, 252] },
                didDrawPage: (data) => {
                    const currentPage = doc.internal.getNumberOfPages();
                    doc.setDrawColor(203, 213, 225);
                    doc.line(12, pageHeight - 9, pageWidth - 12, pageHeight - 9);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(6.5);
                    doc.setTextColor(100, 116, 139);
                    doc.text('Workforce360 AI · DEVIOZ', 12, pageHeight - 5);
                    doc.text(`Página ${currentPage}`, pageWidth - 12, pageHeight - 5, { align: 'right' });

                    if (data.pageNumber > 1) {
                        doc.setTextColor(5, 48, 52);
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(8);
                        doc.text(`${reportTitle} · Workforce360 AI`, 12, 7);
                    }
                }
            });

            doc.save(`${fileName}.pdf`);
        } catch (error) {
            console.error(error);
            alert('No se pudo generar el PDF. Revisa la consola del navegador para más detalle.');
        } finally {
            setBusy(pdfButton, false);
        }
    }

    excelButton?.addEventListener('click', exportExcel);
    pdfButton?.addEventListener('click', exportPdf);
})();
