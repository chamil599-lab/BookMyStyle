export default function TopTable({ title, rows = [], columns = [] }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-5">
      <h3 className="text-sm font-semibold text-gray-700 mb-4">{title}</h3>
      {rows.length === 0 ? (
        <p className="text-gray-400 text-sm text-center py-8">No data available</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-100">
              <th className="text-left pb-2 font-medium text-gray-500 w-8">#</th>
              {columns.map((col) => (
                <th key={col.key} className={`pb-2 font-medium text-gray-500 ${col.align === 'right' ? 'text-right' : 'text-left'}`}>
                  {col.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.map((row, i) => (
              <tr key={i} className="border-b border-gray-50 last:border-0">
                <td className="py-2.5 text-gray-400">{i + 1}</td>
                {columns.map((col) => (
                  <td key={col.key} className={`py-2.5 ${col.align === 'right' ? 'text-right' : ''}`}>
                    {col.render ? col.render(row[col.key], row) : row[col.key]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
