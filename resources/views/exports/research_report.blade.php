<table>
    <thead>
    <tr>
        <th>No</th>
        <th>Judul Penelitian</th>
        <th>Peneliti Utama</th>
        <th>Universitas</th>
        <th>Tahun</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row->judul }}</td>
            <td>{{ $row->peneliti }}</td>
            <td>{{ $row->universitas }}</td>
            <td>{{ $row->tahun }}</td>
            <td>{{ $row->status }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
