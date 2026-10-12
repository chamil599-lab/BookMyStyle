import { useState } from "react";
import { Link } from "react-router-dom";

export default function MostPopular() {
  const [timeFilter, setTimeFilter] = useState("all");
  const [categoryFilter, setCategoryFilter] = useState("all");

  // Custom SVG Icons
  const FireIcon = () => (
    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
      <path fillRule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clipRule="evenodd" />
    </svg>
  );

  const EyeIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
    </svg>
  );

  const HeartIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
    </svg>
  );

  const ClockIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  );

  const popularPosts = [
    {
      id: 1,
      title: "The Complete Guide to React Hooks in 2024",
      excerpt: "Master React Hooks with this comprehensive guide covering useState, useEffect, and custom hooks with real-world examples.",
      author: "Sarah Chen",
      date: "Dec 15, 2023",
      readTime: "12 min read",
      views: "45.2K",
      likes: "3.2K",
      category: "React",
      trending: true,
      rank: 1
    },
    {
      id: 2,
      title: "TypeScript Best Practices for Large Applications",
      excerpt: "Learn how to scale your TypeScript applications with these proven patterns and practices from industry experts.",
      author: "Mike Johnson",
      date: "Dec 12, 2023",
      readTime: "15 min read",
      views: "38.7K",
      likes: "2.8K",
      category: "TypeScript",
      trending: true,
      rank: 2
    },
    {
      id: 3,
      title: "Building Scalable APIs with Node.js and Express",
      excerpt: "Discover the secrets to building robust and scalable REST APIs that can handle millions of requests.",
      author: "Alex Rodriguez",
      date: "Dec 10, 2023",
      readTime: "18 min read",
      views: "35.1K",
      likes: "2.5K",
      category: "Backend",
      trending: false,
      rank: 3
    },
    {
      id: 4,
      title: "CSS Grid vs Flexbox: When to Use What",
      excerpt: "A practical guide to choosing between CSS Grid and Flexbox for your layout needs with real examples.",
      author: "Emma Wilson",
      date: "Dec 8, 2023",
      readTime: "10 min read",
      views: "32.8K",
      likes: "2.1K",
      category: "CSS",
      trending: true,
      rank: 4
    },
    {
      id: 5,
      title: "The Future of Web Development: AI and Beyond",
      excerpt: "Exploring how artificial intelligence is revolutionizing web development and what's coming next.",
      author: "David Kim",
      date: "Dec 5, 2023",
      readTime: "14 min read",
      views: "29.4K",
      likes: "1.9K",
      category: "AI",
      trending: false,
      rank: 5
    },
    {
      id: 6,
      title: "Mastering Next.js 14: Server Components Deep Dive",
      excerpt: "Everything you need to know about Next.js 14 server components and the new App Router architecture.",
      author: "Lisa Wang",
      date: "Dec 3, 2023",
      readTime: "20 min read",
      views: "27.6K",
      likes: "1.7K",
      category: "Next.js",
      trending: true,
      rank: 6
    },
    {
      id: 7,
      title: "Database Design Patterns for Modern Applications",
      excerpt: "Learn essential database design patterns that will make your applications faster and more maintainable.",
      author: "Tom Harris",
      date: "Nov 30, 2023",
      readTime: "16 min read",
      views: "25.3K",
      likes: "1.5K",
      category: "Database",
      trending: false,
      rank: 7
    },
    {
      id: 8,
      title: "The Ultimate Guide to Web Performance Optimization",
      excerpt: "Practical techniques to make your website load faster and improve user experience significantly.",
      author: "Maria Garcia",
      date: "Nov 28, 2023",
      readTime: "22 min read",
      views: "23.8K",
      likes: "1.4K",
      category: "Performance",
      trending: true,
      rank: 8
    },
    {
      id: 9,
      title: "Microservices Architecture: Lessons from Production",
      excerpt: "Real-world lessons and best practices for building and maintaining microservices at scale.",
      author: "James Miller",
      date: "Nov 25, 2023",
      readTime: "25 min read",
      views: "21.9K",
      likes: "1.3K",
      category: "Architecture",
      trending: false,
      rank: 9
    },
    {
      id: 10,
      title: "Modern JavaScript Features You Should Be Using",
      excerpt: "Explore the latest JavaScript features that will make your code cleaner and more efficient.",
      author: "Sophia Lee",
      date: "Nov 22, 2023",
      readTime: "11 min read",
      views: "20.5K",
      likes: "1.2K",
      category: "JavaScript",
      trending: true,
      rank: 10
    }
  ];

  const categories = ["all", "React", "TypeScript", "Next.js", "JavaScript", "CSS", "Backend", "AI", "Database", "Performance", "Architecture"];

  const timeFilters = [
    { value: "all", label: "All Time" },
    { value: "month", label: "This Month" },
    { value: "week", label: "This Week" },
    { value: "today", label: "Today" }
  ];

  const getRankColor = (rank) => {
    if (rank === 1) return "from-yellow-400 to-orange-500";
    if (rank === 2) return "from-gray-400 to-gray-600";
    if (rank === 3) return "from-amber-600 to-amber-800";
    return "from-blue-500 to-purple-600";
  };

  const getRankIcon = (rank) => {
    if (rank === 1) return "🥇";
    if (rank === 2) return "🥈";
    if (rank === 3) return "🥉";
    return `#${rank}`;
  };

  return (
    <div className="min-h-screen bg-linear-to-br from-gray-50 to-blue-50/30 pt-20">
      {/* Header */}
      <div className="bg-white/80 backdrop-blur-xs border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="text-center">
            <div className="flex items-center justify-center space-x-3 mb-4">
              <div className="p-3 bg-linear-to-r from-orange-500 to-red-500 rounded-2xl">
                <FireIcon />
              </div>
              <h1 className="text-4xl lg:text-5xl font-bold text-gray-900">
                Most Popular
              </h1>
            </div>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Discover the articles that are capturing everyone's attention. 
              These are the most-read and most-loved posts from our community.
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex flex-col lg:flex-row gap-4 justify-between items-center mb-8">
          <div className="flex flex-wrap gap-2">
            {timeFilters.map((filter) => (
              <button
                key={filter.value}
                onClick={() => setTimeFilter(filter.value)}
                className={`px-4 py-2 rounded-full font-medium transition-all duration-300 ${
                  timeFilter === filter.value
                    ? "bg-blue-600 text-white shadow-lg"
                    : "bg-white text-gray-700 hover:bg-gray-50 shadow-xs"
                }`}
              >
                {filter.label}
              </button>
            ))}
          </div>
          
          <select
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
            className="px-4 py-2 bg-white border border-gray-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-xs"
          >
            {categories.map((category) => (
              <option key={category} value={category}>
                {category === "all" ? "All Categories" : category}
              </option>
            ))}
          </select>
        </div>

        {/* Popular Posts Grid */}
        <div className="grid lg:grid-cols-2 gap-6">
          {popularPosts.map((post) => (
            <Link
              key={post.id}
              to={`/post/${post.id}`}
              className="group bg-white rounded-2xl shadow-xs hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 hover:border-blue-200"
            >
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex items-center space-x-3">
                    <div className={`w-12 h-12 bg-linear-to-r ${getRankColor(post.rank)} rounded-xl flex items-center justify-center text-white font-bold text-lg`}>
                      {getRankIcon(post.rank)}
                    </div>
                    <div>
                      <span className="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        {post.category}
                      </span>
                      {post.trending && (
                        <span className="inline-flex items-center ml-2 px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                          <FireIcon className="w-3 h-3 mr-1" />
                          Trending
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                <h2 className="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300 mb-3 line-clamp-2">
                  {post.title}
                </h2>
                
                <p className="text-gray-600 mb-4 line-clamp-2">
                  {post.excerpt}
                </p>

                <div className="flex items-center justify-between text-sm text-gray-500">
                  <div className="flex items-center space-x-4">
                    <span className="font-medium text-gray-900">{post.author}</span>
                    <span>{post.date}</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <ClockIcon />
                    <span>{post.readTime}</span>
                  </div>
                </div>

                {/* Engagement Stats */}
                <div className="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                  <div className="flex items-center space-x-4 text-sm text-gray-500">
                    <div className="flex items-center space-x-1">
                      <EyeIcon />
                      <span>{post.views}</span>
                    </div>
                    <div className="flex items-center space-x-1">
                      <HeartIcon />
                      <span>{post.likes}</span>
                    </div>
                  </div>
                  <div className="text-xs text-gray-400">
                    Rank #{post.rank}
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>

        {/* Load More Button */}
        <div className="text-center mt-12">
          <button className="px-8 py-3 bg-linear-to-r from-blue-600 to-purple-600 text-white rounded-lg font-medium hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-300 shadow-md">
            Load More Articles
          </button>
        </div>

        {/* Stats Section */}
        <div className="mt-16 bg-linear-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white">
          <div className="grid md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-3xl font-bold mb-2">500K+</div>
              <div className="text-blue-100">Total Reads</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">50K+</div>
              <div className="text-blue-100">Likes This Month</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">1.2M+</div>
              <div className="text-blue-100">Page Views</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">95%</div>
              <div className="text-blue-100">Reader Satisfaction</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}